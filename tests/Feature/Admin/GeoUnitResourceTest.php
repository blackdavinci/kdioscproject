<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\GeoUnits\Pages\CreateGeoUnit;
use App\Filament\Admin\Resources\GeoUnits\Pages\ListGeoUnits;
use App\Models\GeoUnit;
use App\Models\PlatformUser;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $superAdmin = PlatformUser::factory()->create();
    $session = confirmTwoFactor($superAdmin, 'admin');
    $this->actingAs($superAdmin, 'platform')->withSession(validTwoFactorSession($session));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('liste les unités administratives nationales au super-admin', function (): void {
    $region = GeoUnit::factory()->region()->create(['name' => 'Kindia']);
    $prefecture = GeoUnit::factory()->prefecture($region)->create(['name' => 'Dubréka']);

    Livewire::test(ListGeoUnits::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$region, $prefecture]);
});

it('permet d’ajouter manuellement une commune manquante rattachée à sa préfecture (RG-22)', function (): void {
    $prefecture = GeoUnit::factory()->prefecture()->create(['name' => 'Conakry']);

    Livewire::test(CreateGeoUnit::class)
        ->fillForm([
            'name' => 'Sonfonia',
            'level' => 3,
            'parent_id' => $prefecture->id,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('geo_units', [
        'name' => 'Sonfonia',
        'level' => 3,
        'parent_id' => $prefecture->id,
        'active' => true,
    ]);
});

it('génère automatiquement un P-code distinctif GNX (jamais écrasé par l’import)', function (): void {
    Livewire::test(CreateGeoUnit::class)
        ->fillForm([
            'name' => 'Nouvelle région',
            'level' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $unit = GeoUnit::where('name', 'Nouvelle région')->firstOrFail();

    expect($unit->pcode)->toStartWith('GNX-');
});
