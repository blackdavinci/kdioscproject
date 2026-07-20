<?php

declare(strict_types=1);

use App\Enums\IndicatorDirection;
use App\Filament\App\Pages\IndicatorProgress;
use App\Filament\App\Resources\ResultFrameworks\ResultFrameworkResource;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootProgress(Organization $org, string $role): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole($role);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($user);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('calcule le tableau réalisé vs cible par période (RGSE-07)', function (): void {
    bootProgress($this->org, 'responsable_se');

    $indicator = Indicator::factory()->create([
        'organization_id' => $this->org->id,
        'direction' => IndicatorDirection::Croissant,
    ]);
    $indicator->targets()->create([
        'period_label' => '2026-T1',
        'period_start' => now()->startOfYear(),
        'period_end' => now()->startOfYear()->addMonths(3),
        'target_value' => 100,
    ]);
    $indicator->values()->create([
        'period_label' => '2026-T1',
        'period_start' => now()->startOfYear(),
        'period_end' => now()->startOfYear()->addMonths(3),
        'value' => 80,
    ]);

    $page = Livewire::test(IndicatorProgress::class);
    $page->set('indicatorId', $indicator->id);
    $rows = $page->instance()->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['label'])->toBe('2026-T1')
        ->and((float) $rows[0]['target'])->toBe(100.0)
        ->and(round($rows[0]['attainment'], 2))->toBe(0.8);
});

it('réserve les cadres de résultats à la S&E / gestion projet, jamais au bailleur (RGSE-08)', function (): void {
    bootProgress($this->org, 'chef_projet');
    expect(ResultFrameworkResource::canAccess())->toBeTrue();

    bootProgress($this->org, 'bailleur');
    expect(ResultFrameworkResource::canAccess())->toBeFalse();
});
