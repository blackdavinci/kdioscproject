<?php

declare(strict_types=1);

use App\Filament\App\Resources\Localities\LocalityResource;
use App\Filament\App\Resources\Localities\Pages\CreateLocality;
use App\Models\GeoUnit;
use App\Models\Locality;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);

    // Petit arbre géographique : région → préfecture → sous-préfecture.
    $this->region = GeoUnit::create(['pcode' => 'GN010', 'level' => 1, 'name' => 'Boké']);
    $this->prefecture = GeoUnit::create(['pcode' => 'GN010001', 'level' => 2, 'name' => 'Boffa', 'parent_id' => $this->region->id]);
    $this->subPrefecture = GeoUnit::create(['pcode' => 'GN01000101', 'level' => 3, 'name' => 'Boffa Centre', 'parent_id' => $this->prefecture->id]);

    $this->org = Organization::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->org);
    app(TenantContext::class)->set($this->org->id);
});

it('crée une localité via le sélecteur géo en cascade, rattachée à une sous-préfecture (RG-23)', function (): void {
    Livewire::test(CreateLocality::class)
        ->fillForm([
            'region_id' => $this->region->id,
            'prefecture_id' => $this->prefecture->id,
            'geo_unit_id' => $this->subPrefecture->id,
            'name' => 'Village Konta',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $locality = Locality::withoutGlobalScopes()->where('name', 'Village Konta')->sole();

    expect($locality->organization_id)->toBe($this->org->id)
        ->and($locality->geo_unit_id)->toBe($this->subPrefecture->id);
});

it('autorise admin/chef de projet/responsable S&E, refuse les autres (matrice §6)', function (): void {
    expect(LocalityResource::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->org->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(LocalityResource::canAccess())->toBeFalse();
});
