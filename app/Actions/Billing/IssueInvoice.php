<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Settings\BillingSettings;
use Illuminate\Support\Carbon;

/**
 * Émet la facture de la prochaine période d'un abonnement (RGF-06). Idempotent :
 * s'il existe déjà une facture `pending` pour cette période, elle est renvoyée telle
 * quelle (jamais de double facturation).
 */
class IssueInvoice
{
    public function handle(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        $existing = Invoice::query()
            ->where('subscription_id', $subscription->getKey())
            ->where('status', InvoiceStatus::Pending->value)
            ->whereDate('period_start', $periodStart->toDateString())
            ->first();

        if ($existing instanceof Invoice) {
            return $existing;
        }

        $plan = $subscription->plan()->firstOrFail();
        $graceDays = app(BillingSettings::class)->grace_days;

        return Invoice::create([
            'organization_id' => $subscription->organization_id,
            'subscription_id' => $subscription->getKey(),
            'number' => $this->nextNumber(),
            'amount_gnf' => $plan->amount_gnf,
            'currency' => 'GNF',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'status' => InvoiceStatus::Pending,
            'due_date' => now()->addDays($graceDays)->toDateString(),
            'issued_at' => now(),
        ]);
    }

    protected function nextNumber(): string
    {
        $year = now()->year;
        $count = Invoice::query()->whereYear('issued_at', $year)->count() + 1;

        return sprintf('FAC-%d-%05d', $year, $count);
    }
}
