<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IndicatorDirection;
use App\Enums\LogframeNodeType;
use App\Enums\PeriodType;
use App\Models\Indicator;
use App\Models\LogframeNode;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicator>
 */
class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory()->create();
        $node = LogframeNode::factory()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'type' => LogframeNodeType::Resultat,
        ]);

        return [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'logframe_node_id' => $node->id,
            'code' => 'IND-'.fake()->unique()->numerify('###'),
            'label' => fake()->sentence(4),
            'unit' => 'personnes',
            'direction' => IndicatorDirection::Croissant,
            'period_type' => PeriodType::Trimestriel,
            'disaggregations' => ['sex' => true, 'age' => false, 'locality' => false],
        ];
    }
}
