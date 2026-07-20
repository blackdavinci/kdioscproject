<?php

declare(strict_types=1);

use App\Enums\DisaggregationDimension;
use App\Filament\App\Resources\Indicators\Pages\EditIndicator;
use App\Filament\App\Resources\Indicators\RelationManagers\ValuesRelationManager;
use App\Filament\App\Resources\Indicators\Support\ValueDisaggregation;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootDisagg(Organization $org): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole('responsable_se');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($user);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('signale une somme d’axe incohérente avec la valeur (RGSE-04)', function (): void {
    app(TenantContext::class)->set($this->org->id);
    $indicator = Indicator::factory()->create([
        'organization_id' => $this->org->id,
        'disaggregations' => ['sex' => true, 'age' => false, 'locality' => false],
    ]);

    $extracted = ['sex' => ['femme' => 6, 'homme' => 3], 'age' => [], 'locality' => []];
    $issues = ValueDisaggregation::issues($indicator, 10, $extracted);

    expect($issues)->toHaveCount(1)
        ->and($issues[0])->toContain('sexe');
});

it('enregistre la ventilation d’une valeur via le formulaire (RGSE-04)', function (): void {
    bootDisagg($this->org);
    $indicator = Indicator::factory()->create([
        'organization_id' => $this->org->id,
        'disaggregations' => ['sex' => true, 'age' => false, 'locality' => false],
    ]);

    Livewire::test(ValuesRelationManager::class, [
        'ownerRecord' => $indicator,
        'pageClass' => EditIndicator::class,
    ])
        ->callTableAction('create', data: [
            'period_label' => '2026-T1',
            'value' => 10,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonths(3)->toDateString(),
            'source' => 'manuelle',
            'disagg' => ['sex' => ['femme' => 6, 'homme' => 4]],
        ])
        ->assertHasNoTableActionErrors();

    $value = $indicator->values()->firstOrFail();
    expect($value->disaggregations()->where('dimension', DisaggregationDimension::Sex->value)->count())->toBe(2)
        ->and((int) $value->disaggregations()->where('key', 'femme')->value('count'))->toBe(6);
});

it('bloque une ventilation incohérente quand l’OSC l’impose (RGSE-04)', function (): void {
    bootDisagg($this->org);
    $this->org->update(['settings' => ['enforce_disaggregation' => true]]);
    $indicator = Indicator::factory()->create([
        'organization_id' => $this->org->id,
        'disaggregations' => ['sex' => true, 'age' => false, 'locality' => false],
    ]);

    Livewire::test(ValuesRelationManager::class, [
        'ownerRecord' => $indicator,
        'pageClass' => EditIndicator::class,
    ])
        ->callTableAction('create', data: [
            'period_label' => '2026-T1',
            'value' => 10,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonths(3)->toDateString(),
            'source' => 'manuelle',
            'disagg' => ['sex' => ['femme' => 6, 'homme' => 1]],
        ])
        ->assertHasTableActionErrors();

    expect($indicator->values()->count())->toBe(0);
});
