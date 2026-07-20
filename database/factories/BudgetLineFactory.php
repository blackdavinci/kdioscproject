<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BudgetLine;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    protected $model = BudgetLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'label' => fake()->sentence(3),
            'amount_gnf' => fake()->numberBetween(1_000_000, 500_000_000),
            'threshold_percent' => 80,
        ];
    }
}
