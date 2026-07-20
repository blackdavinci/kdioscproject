<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgeBracket;
use App\Enums\Sex;
use App\Models\Beneficiary;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'BEN-'.fake()->unique()->numerify('#####'),
            'sex' => fake()->randomElement(Sex::cases()),
            'age_bracket' => fake()->randomElement(AgeBracket::cases()),
            'full_name' => fake()->name(),
            'contact' => fake()->phoneNumber(),
        ];
    }
}
