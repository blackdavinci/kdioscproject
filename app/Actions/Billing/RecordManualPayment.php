<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\PlatformUser;

/**
 * Enregistre un règlement reçu hors ligne (virement, espèces) par le super-admin
 * (RGF-07, scénario B) et solde la facture — même effet qu'un paiement Djomy.
 */
class RecordManualPayment
{
    public function handle(Invoice $invoice, PaymentMethod $method, ?PlatformUser $recordedBy = null): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $invoice->getKey(),
            'organization_id' => $invoice->organization_id,
            'amount_gnf' => $invoice->amount_gnf,
            'method' => $method,
            'status' => PaymentStatus::Pending,
            'recorded_by' => $recordedBy?->getKey(),
        ]);

        (new SettleInvoiceFromPayment)->handle($invoice, $payment);

        return $payment;
    }
}
