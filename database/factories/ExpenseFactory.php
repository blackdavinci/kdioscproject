<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseKind;
use App\Models\BudgetLine;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $line = BudgetLine::factory()->create();

        return [
            'organization_id' => $line->organization_id,
            'project_id' => $line->project_id,
            'budget_line_id' => $line->id,
            'kind' => ExpenseKind::Realisee,
            'label' => fake()->sentence(3),
            'amount_gnf' => fake()->numberBetween(100_000, 50_000_000),
            'spent_on' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function commitment(): static
    {
        return $this->state(['kind' => ExpenseKind::Engagement]);
    }
}
