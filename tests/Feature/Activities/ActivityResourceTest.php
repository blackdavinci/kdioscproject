<?php

declare(strict_types=1);

use App\Enums\ActivityStatus;
use App\Enums\DisaggregationPhase;
use App\Enums\LogframeNodeType;
use App\Filament\App\Resources\Activities\ActivityResource;
use App\Filament\App\Resources\Activities\Pages\CreateActivity;
use App\Filament\App\Resources\Activities\Pages\EditActivity;
use App\Models\Activity;
use App\Models\LogframeNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function actInOrg(Organization $org, string $role): User
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

function activityNode(Organization $org): array
{
    return app(TenantContext::class)->runFor($org->id, function () use ($org): array {
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $node = LogframeNode::factory()->create([
            'organization_id' => $org->id,
            'project_id' => $project->id,
            'type' => LogframeNodeType::Activite,
        ]);

        return [$project, $node];
    });
}

it('planifie une activité rattachée à un nœud du cadre logique (RGA-01)', function (): void {
    actInOrg($this->org, 'chef_projet');
    [$project, $node] = activityNode($this->org);

    Livewire::test(CreateActivity::class)
        ->fillForm([
            'project_id' => $project->id,
            'logframe_node_id' => $node->id,
            'title' => 'Formation WASH',
            'planned_start' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $activity = Activity::where('title', 'Formation WASH')->firstOrFail();
    expect($activity->status)->toBe(ActivityStatus::Planifiee);
});

it('saisit une réalisation différée et enregistre les désagrégations (RGA-04/05)', function (): void {
    $agent = actInOrg($this->org, 'agent_terrain');
    [$project, $node] = activityNode($this->org);
    $activity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'logframe_node_id' => $node->id,
    ]);
    ProjectMember::create(['project_id' => $project->id, 'user_id' => $agent->id]);

    Livewire::test(EditActivity::class, ['record' => $activity->getKey()])
        ->fillForm([
            'realized_at' => now()->subMonth()->toDateString(),
            'description' => 'Séance réalisée',
            'disagg' => ['real' => [
                'total' => 10,
                'sex' => ['femme' => 6, 'homme' => 4],
                'age' => ['0_5' => 0, '6_14' => 0, '15_24' => 10, '25_59' => 0, '60_plus' => 0],
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $activity->refresh();
    expect($activity->status)->toBe(ActivityStatus::Realisee)
        ->and($activity->disaggregations()->where('phase', DisaggregationPhase::Real->value)->where('dimension', 'sex')->count())->toBe(2);
});

it('bloque une désagrégation incohérente quand l’OSC l’impose (RGA-05b)', function (): void {
    $agent = actInOrg($this->org, 'agent_terrain');
    $this->org->update(['settings' => ['enforce_disaggregation' => true]]);
    [$project, $node] = activityNode($this->org);
    $activity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'logframe_node_id' => $node->id,
    ]);
    ProjectMember::create(['project_id' => $project->id, 'user_id' => $agent->id]);

    Livewire::test(EditActivity::class, ['record' => $activity->getKey()])
        ->fillForm([
            'realized_at' => now()->subDay()->toDateString(),
            'disagg' => ['real' => [
                'total' => 10,
                'sex' => ['femme' => 6, 'homme' => 2],
                'age' => ['15_24' => 10],
            ]],
        ])
        ->call('save')
        ->assertHasFormErrors();

    expect($activity->fresh()->disaggregations()->count())->toBe(0);
});

it('interdit l’accès des activités au rôle bailleur', function (): void {
    actInOrg($this->org, 'bailleur');

    expect(ActivityResource::canAccess())->toBeFalse();
});
