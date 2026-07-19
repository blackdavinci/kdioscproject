<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GeoUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeoUnit>
 */
class GeoUnitFactory extends Factory
{
    protected $model = GeoUnit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pcode' => 'GN'.fake()->unique()->numerify('########'),
            'level' => 1,
            'parent_id' => null,
            'name' => fake()->unique()->city(),
            'active' => true,
        ];
    }

    public function region(): static
    {
        return $this->state(['level' => 1, 'parent_id' => null]);
    }

    public function prefecture(GeoUnit|string|null $parent = null): static
    {
        return $this->state([
            'level' => 2,
            'parent_id' => $parent instanceof GeoUnit ? $parent->getKey() : $parent,
        ]);
    }

    public function commune(GeoUnit|string|null $parent = null): static
    {
        return $this->state([
            'level' => 3,
            'parent_id' => $parent instanceof GeoUnit ? $parent->getKey() : $parent,
        ]);
    }
}
