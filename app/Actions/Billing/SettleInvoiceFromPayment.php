<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\Organizations\SetOrganizationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\SuspensionSource;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Settings\BillingSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Solde une facture à partir d'un règlement réussi (RGF-07) : marque le paiement et la
 * facture payés, prolonge la période d'abonnement et **réactive automatiquement**
 * l'organisation si elle était suspendue **pour impayé** (RGF-11) — jamais une
 * suspension manuelle. Idempotent : ne fait rien si la facture est déjà payée.
 */
class SettleInvoiceFromPayment
{
    public function handle(Invoice $invoice, Payment $payment): void
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($invoice, $payment): void {
            $payment->forceFill(['status' => PaymentStatus::Succeeded, 'paid_at' => now()])->save();
            $invoice->forceFill(['status' => InvoiceStatus::Paid, 'paid_at' => now()])->save();

            $subscription = $invoice->subscription()->firstOrFail();
            $plan = $subscription->plan()->firstOrFail();

            $start = ($subscription->current_period_end !== null && $subscription->current_period_end->isFuture())
                ? $subscription->current_period_end
                : now();

            $subscription->forceFill([
                'status' => SubscriptionStatus::Active,
                'current_period_start' => $subscription->current_period_start ?? now(),
                'current_period_end' => $this->addPeriod($start, $plan->period),
                'grace_until' => null,
            ])->save();

            $this->reactivateIfBillingSuspended($invoice);
        });
    }

    protected function reactivateIfBillingSuspended(Invoice $invoice): void
    {
        if (! app(BillingSettings::class)->auto_reactivate_on_payment) {
            return;
        }

        $organization = $invoice->organization()->first();

        if ($organization !== null
            && ! $organization->isActive()
            && $organization->suspended_source === SuspensionSource::Billing) {
            (new SetOrganizationStatus)->reactivate($organization);
        }
    }

    protected function addPeriod(Carbon $from, string $period): Carbon
    {
        return $period === 'month' ? $from->copy()->addMonth() : $from->copy()->addYear();
    }
}
