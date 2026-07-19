<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Organization;

/**
 * Crée l'abonnement d'une organisation à sa création (RGF-04). Démarre en période
 * d'essai (durée du plan) ; à l'échéance de l'essai, le cycle de vie émet la première
 * facture. Idempotent : ne recrée pas un abonnement existant.
 */
class CreateSubscription
{
    public function handle(Organization $organization, ?Plan $plan = null): Subscription
    {
        $existing = Subscription::query()->where('organization_id', $organization->getKey())->first();

        if ($existing instanceof Subscription) {
            return $existing;
        }

        $plan ??= Plan::query()->where('is_active', true)->firstOrFail();

        return Subscription::create([
            'organization_id' => $organization->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays($plan->trial_days),
        ]);
    }
}
