<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\LogframeNodeType;
use App\Models\Activity;
use App\Models\LogframeNode;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();
        $node = LogframeNode::factory()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'type' => LogframeNodeType::Activite,
        ]);

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'logframe_node_id' => $node->id,
            'title' => fake()->sentence(3),
            'planned_start' => fake()->dateTimeBetween('-3 months', '+1 month'),
            'status' => ActivityStatus::Planifiee,
        ];
    }

    public function realized(): static
    {
        return $this->state([
            'status' => ActivityStatus::Realisee,
            'realized_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'description' => fake()->paragraph(),
        ]);
    }
}
