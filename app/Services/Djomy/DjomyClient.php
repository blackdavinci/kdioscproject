<?php

declare(strict_types=1);

namespace App\Services\Djomy;

use App\Settings\BillingSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP Djomy (RGF-12/13), porté de l'implémentation éprouvée du projet
 * `sagefemme`. Client « mince » : il ne connaît ni facture ni abonnement — la logique
 * métier (réconciliation, solde de facture) vit dans les actions Billing.
 *
 * Auth : X-API-KEY = "{client_id}:{hmac_sha256(client_id, client_secret)}" → POST /auth
 * → accessToken (caché ~55 min). En-tête X-PARTNER-DOMAIN si configuré.
 */
class DjomyClient
{
    public function __construct(protected BillingSettings $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->djomy_enabled
            && ! empty($this->settings->djomy_client_id)
            && ! empty($this->settings->djomy_client_secret);
    }

    /**
     * Crée un lien de paiement (encaissement mobile money).
     *
     * @param  array<string, mixed>  $params  amountToPay, merchantReference, linkName,
     *                                        description, returnUrl, cancelUrl, phoneNumber, metadata
     * @return array{success: bool, payment_url?: string|null, reference?: string|null, data?: array<string, mixed>, error?: string}
     */
    public function createPaymentLink(array $params): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'error' => 'Djomy non configuré'];
        }

        $http = $this->authenticatedRequest();

        if (! $http instanceof PendingRequest) {
            return ['success' => false, 'error' => "Échec d'authentification Djomy"];
        }

        try {
            $payload = array_merge([
                'countryCode' => 'GN',
                'usageType' => 'UNIQUE',
            ], $params);

            $response = $http->post($this->settings->getApiUrl().'links', $payload);

            if ($response->successful()) {
                /** @var array<string, mixed> $body */
                $body = $response->json() ?? [];
                /** @var array<string, mixed> $data */
                $data = is_array($body['data'] ?? null) ? $body['data'] : $body;

                return [
                    'success' => true,
                    'payment_url' => $this->stringOrNull($data['paymentPageUrl'] ?? $data['payment_url'] ?? $data['url'] ?? null),
                    'reference' => $this->stringOrNull($data['reference'] ?? null),
                    'data' => $body,
                ];
            }

            return ['success' => false, 'error' => $this->errorMessage($response->json()), 'data' => (array) $response->json()];
        } catch (\Throwable $e) {
            Log::error('Djomy: exception createPaymentLink', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Erreur de connexion au service de paiement'];
        }
    }

    /**
     * Interroge le statut réel d'une transaction (CREATED|PENDING|SUCCESS|FAILED|CANCELLED).
     *
     * @return array{success: bool, status?: string|null, data?: array<string, mixed>, error?: string}
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $http = $this->authenticatedRequest();

        if (! $http instanceof PendingRequest) {
            return ['success' => false, 'error' => 'Auth Djomy impossible'];
        }

        $response = $http->get($this->settings->getApiUrl().'payments/'.urlencode($transactionId).'/status');

        if (! $response->successful()) {
            return ['success' => false, 'error' => 'HTTP '.$response->status()];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        /** @var array<string, mixed> $data */
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        return ['success' => true, 'status' => $this->stringOrNull($data['status'] ?? null), 'data' => $data];
    }

    /**
     * Vérifie la signature HMAC d'un webhook Djomy (RGF-13).
     * En-tête X-Webhook-Signature: v1:<hmac_sha256(corps_brut, client_secret)>.
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = $this->settings->djomy_webhook_secret ?: $this->settings->djomy_client_secret;

        if (empty($secret) || $signature === '') {
            return false;
        }

        $provided = str_starts_with($signature, 'v1:') ? substr($signature, 3) : $signature;
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $provided);
    }

    protected function authenticatedRequest(): ?PendingRequest
    {
        $token = $this->authenticate();

        if ($token === null) {
            return null;
        }

        return Http::withHeaders(array_merge($this->commonHeaders(), [
            'Authorization' => 'Bearer '.$token,
        ]))->timeout(30);
    }

    protected function authenticate(): ?string
    {
        $clientId = (string) $this->settings->djomy_client_id;
        $cacheKey = 'djomy_access_token_'.md5($clientId);

        return Cache::remember($cacheKey, 3300, function (): ?string {
            $response = Http::withHeaders($this->commonHeaders())
                ->withBody('{}', 'application/json')
                ->post($this->settings->getApiUrl().'auth');

            if ($response->successful()) {
                /** @var array<string, mixed> $data */
                $data = $response->json() ?? [];
                $token = data_get($data, 'data.accessToken') ?? data_get($data, 'access_token');

                if (is_string($token) && $token !== '') {
                    return $token;
                }
            }

            Log::error('Djomy: échec authentification', ['status' => $response->status()]);

            return null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function commonHeaders(): array
    {
        $clientId = (string) $this->settings->djomy_client_id;
        $clientSecret = (string) $this->settings->djomy_client_secret;

        $headers = [
            'X-API-KEY' => $clientId.':'.hash_hmac('sha256', $clientId, $clientSecret),
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->settings->djomy_partner_domain)) {
            $headers['X-PARTNER-DOMAIN'] = (string) $this->settings->djomy_partner_domain;
        }

        return $headers;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    protected function errorMessage(mixed $json): string
    {
        if (is_array($json)) {
            $message = $json['message'] ?? $json['error'] ?? null;
            if (is_string($message)) {
                return $message;
            }
        }

        return 'Erreur lors de la création du lien de paiement';
    }
}
