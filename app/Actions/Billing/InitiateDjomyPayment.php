<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Organization;
use App\Services\Djomy\DjomyClient;

/**
 * Initie un encaissement Djomy pour une facture (RGF-07, scénario C) : crée un
 * règlement `pending` puis un lien de paiement Djomy, et renvoie l'URL de la page de
 * paiement vers laquelle rediriger l'organisation.
 */
class InitiateDjomyPayment
{
    /**
     * @return array{success: bool, payment_url?: string|null, payment?: Payment, error?: string}
     */
    public function handle(Invoice $invoice): array
    {
        $client = app(DjomyClient::class);

        if (! $client->isEnabled()) {
            return ['success' => false, 'error' => 'Le paiement en ligne n’est pas disponible.'];
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->getKey(),
            'organization_id' => $invoice->organization_id,
            'amount_gnf' => $invoice->amount_gnf,
            'method' => PaymentMethod::Djomy,
            'status' => PaymentStatus::Pending,
        ]);

        $organization = $invoice->organization()->first();
        $organizationName = $organization instanceof Organization ? $organization->name : 'Organisation';

        $result = $client->createPaymentLink([
            'amountToPay' => (float) $invoice->amount_gnf,
            'linkName' => 'Abonnement KDI OSC — '.$organizationName,
            'description' => 'Facture '.$invoice->number.' — '.$organizationName,
            'merchantReference' => $invoice->number,
            'returnUrl' => $this->https(url('/facturation/retour?invoice='.$invoice->getKey())),
            'cancelUrl' => $this->https(url('/facturation/annule?invoice='.$invoice->getKey())),
            'metadata' => [
                'payment_id' => (string) $payment->getKey(),
                'invoice_id' => (string) $invoice->getKey(),
                'organization_id' => (string) $invoice->organization_id,
            ],
        ]);

        if ($result['success'] === true) {
            $payment->forceFill([
                'djomy_link_reference' => $result['reference'] ?? null,
                'djomy_response' => $result['data'] ?? [],
            ])->save();

            return ['success' => true, 'payment_url' => $result['payment_url'] ?? null, 'payment' => $payment];
        }

        $payment->forceFill(['status' => PaymentStatus::Failed])->save();

        return ['success' => false, 'error' => $result['error'] ?? 'Échec de l’initiation du paiement.', 'payment' => $payment];
    }

    protected function https(string $url): string
    {
        return str_replace('http://', 'https://', $url);
    }
}
