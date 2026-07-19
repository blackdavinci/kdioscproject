<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\Organizations\SetOrganizationStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\SuspensionSource;
use App\Models\Billing\Subscription;
use App\Settings\BillingSettings;
use Illuminate\Support\Carbon;

/**
 * Fait avancer le cycle de vie des abonnements (RGF-08/09), exécuté quotidiennement
 * (file Horizon `low`) :
 *  - essai échu → 1re facture émise, `past_due`, période ouverte, fenêtre de grâce ;
 *  - période active échue → facture de renouvellement, `past_due`, fenêtre de grâce ;
 *  - `past_due` dont la grâce est écoulée et la facture impayée → `suspended` (impayé).
 *
 * @return array{invoiced: int, suspended: int}
 */
class AdvanceSubscriptionLifecycle
{
    /**
     * @return array{invoiced: int, suspended: int}
     */
    public function handle(): array
    {
        $graceDays = app(BillingSettings::class)->grace_days;
        $now = now();
        $invoiced = 0;
        $suspended = 0;

        // 1. Essais échus.
        $trials = Subscription::query()
            ->where('status', SubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '<=', $now)
            ->get();

        foreach ($trials as $subscription) {
            $this->openPeriodAndInvoice($subscription, $graceDays);
            $invoiced++;
        }

        // 2. Périodes actives échues.
        $lapsed = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', $now)
            ->get();

        foreach ($lapsed as $subscription) {
            $this->openPeriodAndInvoice($subscription, $graceDays);
            $invoiced++;
        }

        // 3. Fenêtre de grâce écoulée → suspension pour impayé.
        $overdue = Subscription::query()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereNotNull('grace_until')
            ->where('grace_until', '<=', $now)
            ->get();

        foreach ($overdue as $subscription) {
            $this->suspend($subscription);
            $suspended++;
        }

        return ['invoiced' => $invoiced, 'suspended' => $suspended];
    }

    protected function openPeriodAndInvoice(Subscription $subscription, int $graceDays): void
    {
        $plan = $subscription->plan()->firstOrFail();
        $periodStart = now();
        $periodEnd = $plan->period === 'month' ? $periodStart->copy()->addMonth() : $periodStart->copy()->addYear();

        (new IssueInvoice)->handle($subscription, $periodStart, $periodEnd);

        $subscription->forceFill([
            'status' => SubscriptionStatus::PastDue,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'grace_until' => Carbon::now()->addDays($graceDays),
        ])->save();
    }

    protected function suspend(Subscription $subscription): void
    {
        $organization = $subscription->organization()->first();

        if ($organization !== null) {
            (new SetOrganizationStatus)->suspend(
                $organization,
                'Abonnement échu — paiement en attente.',
                SuspensionSource::Billing,
            );
        }

        $subscription->forceFill(['status' => SubscriptionStatus::Suspended])->save();
    }
}
