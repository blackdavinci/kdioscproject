<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            // La fiche membre est créée dans la même organisation que le compte.
            'team_member_id' => fn (array $attributes): string => TeamMember::factory()
                ->create(['organization_id' => $attributes['organization_id']])
                ->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'locale' => 'fr',
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function invited(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Invited,
            'password' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }
}
