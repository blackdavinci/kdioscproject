<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();

        return [
            'subscription_id' => $subscription->id,
            'organization_id' => $subscription->organization_id,
            'number' => 'FAC-'.fake()->unique()->numerify('######'),
            'amount_gnf' => 1_500_000,
            'currency' => 'GNF',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addYear()->toDateString(),
            'status' => InvoiceStatus::Pending,
            'due_date' => now()->addDays(7)->toDateString(),
            'issued_at' => now(),
        ];
    }
}
