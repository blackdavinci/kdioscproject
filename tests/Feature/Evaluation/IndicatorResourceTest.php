<?php

declare(strict_types=1);

use App\Enums\IndicatorDirection;
use App\Enums\LogframeNodeType;
use App\Enums\PeriodType;
use App\Filament\App\Resources\Indicators\IndicatorResource;
use App\Filament\App\Resources\Indicators\Pages\CreateIndicator;
use App\Models\Indicator;
use App\Models\LogframeNode;
use App\Models\Organization;
use App\Models\Project;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootSe(Organization $org, string $role): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = \App\Models\User::factory()->create(['organization_id' => $org->id]);
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

it('crée un indicateur rattaché à un nœud du cadre logique (RGSE-01)', function (): void {
    bootSe($this->org, 'responsable_se');
    $project = Project::factory()->create(['organization_id' => $this->org->id]);
    $node = LogframeNode::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'type' => LogframeNodeType::Resultat,
    ]);

    Livewire::test(CreateIndicator::class)
        ->fillForm([
            'project_id' => $project->id,
            'logframe_node_id' => $node->id,
            'label' => 'Personnes ayant accès à l’eau potable',
            'unit' => 'personnes',
            'direction' => IndicatorDirection::Croissant->value,
            'period_type' => PeriodType::Trimestriel->value,
            'disaggregations' => ['sex' => true, 'age' => false, 'locality' => true],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $indicator = Indicator::where('label', 'Personnes ayant accès à l’eau potable')->firstOrFail();
    expect($indicator->logframe_node_id)->toBe($node->id)
        ->and($indicator->hasAxis('sex'))->toBeTrue()
        ->and($indicator->hasAxis('locality'))->toBeTrue()
        ->and($indicator->hasAxis('age'))->toBeFalse();
});

it('réserve les indicateurs à la S&E / direction, jamais au bailleur', function (): void {
    bootSe($this->org, 'responsable_se');
    expect(IndicatorResource::canAccess())->toBeTrue();

    bootSe($this->org, 'bailleur');
    expect(IndicatorResource::canAccess())->toBeFalse();

    bootSe($this->org, 'agent_terrain');
    expect(IndicatorResource::canAccess())->toBeFalse();
});
