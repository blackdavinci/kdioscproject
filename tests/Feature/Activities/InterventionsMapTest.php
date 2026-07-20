<?php

declare(strict_types=1);

use App\Filament\App\Pages\InterventionsMap;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootMap(Organization $org, string $role): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole($role);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($user);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);

    return $user;
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('ne retient que les activités géolocalisées comme points (RGA-11)', function (): void {
    bootMap($this->org, 'responsable_se');

    Activity::factory()->create(['organization_id' => $this->org->id, 'latitude' => 9.64, 'longitude' => -13.57]);
    Activity::factory()->create(['organization_id' => $this->org->id, 'latitude' => null, 'longitude' => null]);

    $points = Livewire::test(InterventionsMap::class)->instance()->points();

    expect($points)->toHaveCount(1)
        ->and($points[0]['lat'])->toBe(9.64);
});

it('réserve la carte à la direction et au S&E (RGA-11)', function (): void {
    bootMap($this->org, 'responsable_se');
    expect(InterventionsMap::canAccess())->toBeTrue();

    bootMap($this->org, 'agent_terrain');
    expect(InterventionsMap::canAccess())->toBeFalse();
});
