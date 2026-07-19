<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Billing\DjomyWebhookEvent;
use App\Models\Billing\Payment;

/**
 * Traite un webhook Djomy déjà authentifié (RGF-13). Idempotent : un même événement
 * rejoué ne solde pas deux fois (SettleInvoiceFromPayment ne fait rien si la facture
 * est déjà payée). Journalise chaque événement.
 */
class HandleDjomyWebhook
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): bool
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $linkReference = $this->str($payload['paymentLinkReference'] ?? $data['paymentLinkReference'] ?? null);
        $transactionId = $this->str($data['transactionId'] ?? $payload['transactionId'] ?? null);
        $status = strtolower((string) $this->str($data['status'] ?? $payload['status'] ?? null));
        $metadataPaymentId = $this->str(data_get($payload, 'metadata.payment_id'));

        $event = DjomyWebhookEvent::create([
            'event_type' => $this->str($payload['eventType'] ?? null),
            'reference' => $linkReference ?? $transactionId,
            'payload' => $payload,
        ]);

        $payment = $this->resolvePayment($linkReference, $metadataPaymentId);

        if (! $payment instanceof Payment) {
            return false;
        }

        $invoice = $payment->invoice()->first();

        if ($invoice === null) {
            return false;
        }

        switch ($status) {
            case 'success':
            case 'successful':
            case 'completed':
                if ($transactionId !== null) {
                    $payment->forceFill(['djomy_transaction_id' => $transactionId])->save();
                }
                (new SettleInvoiceFromPayment)->handle($invoice, $payment);
                break;

            case 'failed':
            case 'declined':
                $payment->forceFill(['status' => PaymentStatus::Failed])->save();
                if (! $invoice->isPaid()) {
                    $invoice->forceFill(['status' => InvoiceStatus::Failed])->save();
                }
                break;

            case 'cancelled':
            case 'canceled':
            case 'expired':
                $payment->forceFill(['status' => PaymentStatus::Cancelled])->save();
                break;
        }

        $event->forceFill(['processed_at' => now()])->save();

        return true;
    }

    protected function resolvePayment(?string $linkReference, ?string $metadataPaymentId): ?Payment
    {
        if ($linkReference !== null) {
            $payment = Payment::query()->where('djomy_link_reference', $linkReference)->latest()->first();
            if ($payment instanceof Payment) {
                return $payment;
            }
        }

        if ($metadataPaymentId !== null) {
            return Payment::query()->whereKey($metadataPaymentId)->first();
        }

        return null;
    }

    protected function str(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
