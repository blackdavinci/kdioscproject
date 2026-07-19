<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Donors\DonorResource as AdminDonorResource;
use App\Filament\Admin\Resources\Sectors\SectorResource as AdminSectorResource;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Filament\App\Resources\Sectors\SectorResource;
use App\Filament\App\Resources\Tags\TagResource;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Sector;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);

    // Base nationale (organization_id nul).
    Sector::create(['name' => 'Santé']);
    Donor::create(['name' => 'Union européenne', 'type' => 'multilateral']);

    $this->org = Organization::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
    $this->admin->assignRole('admin');
});

it('réserve les référentiels aux administrateurs de l’organisation (matrice §6)', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->org);

    expect(SectorResource::canAccess())->toBeTrue()
        ->and(DonorResource::canAccess())->toBeTrue()
        ->and(TagResource::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->org->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(SectorResource::canAccess())->toBeFalse();
});

it('l’OSC voit la base nationale plus ses propres entrées, jamais celles d’une autre (RG-19/20)', function (): void {
    app(TenantContext::class)->runFor($this->org->id, function (): void {
        Sector::create(['name' => 'Secteur propre']);
        Donor::create(['name' => 'Bailleur propre', 'type' => 'private']);
    });

    app(TenantContext::class)->set($this->org->id);

    expect(Sector::pluck('name')->all())->toEqualCanonicalizing(['Santé', 'Secteur propre'])
        ->and(Donor::pluck('name')->all())->toEqualCanonicalizing(['Union européenne', 'Bailleur propre']);
});

it('marque les entrées nationales comme non modifiables par l’OSC (RG-19/20)', function (): void {
    $national = Sector::whereNull('organization_id')->sole();
    $own = app(TenantContext::class)->runFor($this->org->id, fn () => Sector::create(['name' => 'Propre']));

    expect($national->isNational())->toBeTrue()
        ->and($own->isNational())->toBeFalse();
});

it('l’écran super-admin ne gère que la base nationale (organization_id nul)', function (): void {
    // Une entrée propre à une OSC ne doit pas apparaître côté base nationale.
    app(TenantContext::class)->runFor($this->org->id, fn () => Sector::create(['name' => 'Interne OSC']));

    $sectors = AdminSectorResource::getEloquentQuery()->pluck('organization_id')->unique()->all();
    $donors = AdminDonorResource::getEloquentQuery()->pluck('organization_id')->unique()->all();

    expect($sectors)->toBe([null])
        ->and($donors)->toBe([null]);
});
