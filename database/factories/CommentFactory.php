<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $task = Task::factory()->create();

        return [
            'organization_id' => $task->organization_id,
            'commentable_type' => $task->getMorphClass(),
            'commentable_id' => $task->id,
            'user_id' => User::factory()->state(['organization_id' => $task->organization_id]),
            'body' => fake()->sentence(),
        ];
    }
}
