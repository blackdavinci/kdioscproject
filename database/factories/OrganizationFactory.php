<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'sigle' => strtoupper(fake()->lexify('???')),
            'contacts' => [
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
            ],
            'currency' => 'GNF',
            'fiscal_year_start' => 1,
            'status' => OrganizationStatus::Active,
            'settings' => [],
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => OrganizationStatus::Suspended]);
    }
}
