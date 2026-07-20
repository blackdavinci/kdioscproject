<?php

declare(strict_types=1);

use App\Filament\App\Resources\Activities\Support\DuplicateActivitySeries;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
    $this->activity = app(TenantContext::class)->runFor($this->org->id, fn () => Activity::factory()->create([
        'organization_id' => $this->org->id,
        'planned_start' => now()->toDateString(),
    ]));
});

it('génère la fiche d’activité en PDF pour un membre de l’organisation (RGA-09)', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $user = User::factory()->create(['organization_id' => $this->org->id]);
    $user->assignRole('chef_projet');

    $response = $this->actingAs($user)->get(route('activities.sheet', $this->activity));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('refuse les formulaires papier à un membre d’une autre organisation (403)', function (): void {
    $other = Organization::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($other->id);
    $intruder = User::factory()->create(['organization_id' => $other->id]);
    $intruder->assignRole('admin');

    $this->actingAs($intruder)
        ->get(route('activities.attendance', $this->activity))
        ->assertForbidden();
});

it('duplique une activité en série hebdomadaire reliée par recurrence_group_id (RGA-12)', function (): void {
    app(TenantContext::class)->set($this->org->id);

    $created = DuplicateActivitySeries::handle($this->activity, 'weekly', 3);
    $this->activity->refresh();

    expect($created)->toBe(3)
        ->and($this->activity->recurrence_group_id)->not->toBeNull()
        ->and(Activity::where('recurrence_group_id', $this->activity->recurrence_group_id)->count())->toBe(4);

    $second = Activity::where('recurrence_group_id', $this->activity->recurrence_group_id)
        ->where('id', '!=', $this->activity->id)
        ->orderBy('planned_start')
        ->first();

    expect($second->planned_start->toDateString())->toBe($this->activity->planned_start->copy()->addWeek()->toDateString());
});
