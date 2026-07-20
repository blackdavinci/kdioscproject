<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LogframeNodeType;
use App\Models\LogframeNode;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogframeNode>
 */
class LogframeNodeFactory extends Factory
{
    protected $model = LogframeNode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory();

        return [
            'organization_id' => fn (array $attrs) => Project::whereKey($attrs['project_id'])->value('organization_id'),
            'project_id' => $project,
            'type' => LogframeNodeType::Resultat,
            'title' => fake()->sentence(4),
            'position' => 0,
        ];
    }
}
