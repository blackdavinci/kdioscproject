<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessDjomyWebhook;
use App\Services\Djomy\DjomyClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réception des webhooks Djomy (RGF-13). Vérifie la signature HMAC sur le corps brut,
 * puis délègue le traitement (idempotent) à un job sur la file `low`.
 */
class DjomyWebhookController extends Controller
{
    public function handle(Request $request, DjomyClient $client): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = (string) ($request->header('X-Webhook-Signature')
            ?? $request->header('X-Djomy-Signature')
            ?? '');

        if (! $client->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Djomy webhook : signature invalide', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        ProcessDjomyWebhook::dispatch($payload);

        return response()->json(['status' => 'accepted']);
    }
}
