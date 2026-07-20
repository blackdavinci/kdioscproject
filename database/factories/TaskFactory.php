<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => fake()->optional()->dateTimeBetween('-1 week', '+1 month'),
            'priority' => TaskPriority::Normale,
            'status' => TaskStatus::AFaire,
            'position' => 0,
        ];
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
