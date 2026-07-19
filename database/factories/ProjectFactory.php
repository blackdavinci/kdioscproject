<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'organization_id' => Organization::factory(),
            'code' => 'PRJ-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'target_groups' => fake()->sentence(),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start, '+2 years'),
            'status' => ProjectStatus::Brouillon,
        ];
    }

    public function status(ProjectStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
