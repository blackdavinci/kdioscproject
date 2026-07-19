<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now(),
            'current_period_end' => now()->addYear(),
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => null,
            'current_period_end' => null,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::PastDue,
            'current_period_end' => now()->subDay(),
        ]);
    }
}
