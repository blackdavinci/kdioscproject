<?php

declare(strict_types=1);

use App\Enums\LogframeNodeType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Filament\App\Resources\Beneficiaries\Pages\EditBeneficiary;
use App\Filament\App\Resources\Beneficiaries\RelationManagers\ParticipationsRelationManager;
use App\Filament\App\Resources\Indicators\Pages\EditIndicator;
use App\Filament\App\Resources\Indicators\RelationManagers\TargetsRelationManager;
use App\Filament\App\Resources\Projects\Pages\EditProject;
use App\Filament\App\Resources\Projects\RelationManagers\LogframeRelationManager;
use App\Filament\App\Resources\Projects\RelationManagers\MembersRelationManager;
use App\Filament\App\Resources\Projects\RelationManagers\SharesRelationManager;
use App\Filament\App\Resources\Projects\RelationManagers\ZonesRelationManager;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\GeoUnit;
use App\Models\Indicator;
use App\Models\LogframeNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootRm(Organization $org): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($admin);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);

    return $admin;
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
    $this->admin = bootRm($this->org);
    $this->project = Project::factory()->create(['organization_id' => $this->org->id, 'status' => ProjectStatus::EnCours]);
});

it('ajoute un membre à l’équipe projet (avec rôle) sans erreur', function (): void {
    $member = TeamMember::create(['organization_id' => $this->org->id, 'full_name' => 'Animateur X']);
    $role = ProjectRole::create(['organization_id' => $this->org->id, 'name' => 'Animateur']);

    Livewire::test(MembersRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => EditProject::class])
        ->callTableAction('create', data: ['team_member_id' => $member->id, 'project_role_id' => $role->id])
        ->assertHasNoTableActionErrors();

    expect($this->project->members()->count())->toBe(1);
});

it('ajoute un nœud au cadre logique sans erreur', function (): void {
    Livewire::test(LogframeRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => EditProject::class])
        ->callTableAction('create', data: ['type' => LogframeNodeType::Resultat->value, 'title' => 'Résultat 1', 'position' => 0])
        ->assertHasNoTableActionErrors();

    expect($this->project->logframeNodes()->count())->toBe(1);
});

it('ajoute une zone d’intervention sans erreur', function (): void {
    $geo = GeoUnit::create(['pcode' => 'GN-Z1', 'level' => 2, 'name' => 'Kankan']);

    Livewire::test(ZonesRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => EditProject::class])
        ->callTableAction('create', data: ['geo_unit_id' => $geo->id])
        ->assertHasNoTableActionErrors();

    expect($this->project->zones()->count())->toBe(1);
});

it('partage un projet à un compte bailleur sans erreur', function (): void {
    $bailleur = User::factory()->create(['organization_id' => $this->org->id]);
    $bailleur->assignRole(UserRole::Bailleur->value);

    Livewire::test(SharesRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => EditProject::class])
        ->callTableAction('create', data: ['user_id' => $bailleur->id])
        ->assertHasNoTableActionErrors();

    expect($this->project->shares()->count())->toBe(1);
});

it('ajoute une cible d’indicateur sans erreur', function (): void {
    $indicator = Indicator::factory()->create(['organization_id' => $this->org->id, 'project_id' => $this->project->id]);

    Livewire::test(TargetsRelationManager::class, ['ownerRecord' => $indicator, 'pageClass' => EditIndicator::class])
        ->callTableAction('create', data: [
            'period_label' => '2026-T1',
            'target_value' => 100,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonths(3)->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    expect($indicator->targets()->count())->toBe(1);
});

it('rattache une activité à un bénéficiaire (participation) sans erreur', function (): void {
    $node = LogframeNode::factory()->create(['organization_id' => $this->org->id, 'project_id' => $this->project->id, 'type' => LogframeNodeType::Activite]);
    $activity = Activity::factory()->create(['organization_id' => $this->org->id, 'project_id' => $this->project->id, 'logframe_node_id' => $node->id]);
    $beneficiary = Beneficiary::factory()->create(['organization_id' => $this->org->id]);

    Livewire::test(ParticipationsRelationManager::class, ['ownerRecord' => $beneficiary, 'pageClass' => EditBeneficiary::class])
        ->callTableAction('attach', data: ['recordId' => $activity->id])
        ->assertHasNoTableActionErrors();

    expect($beneficiary->activities()->count())->toBe(1);
});
