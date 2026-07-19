<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Models\Billing\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Plan Standard',
            'amount_gnf' => 1_500_000,
            'period' => 'year',
            'trial_days' => 14,
            'is_active' => true,
        ];
    }
}
