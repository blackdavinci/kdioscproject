<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'full_name' => fake()->name(),
            'function' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
