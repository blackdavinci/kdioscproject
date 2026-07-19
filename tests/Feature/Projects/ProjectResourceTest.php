<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Filament\App\Resources\Projects\Pages\CreateProject;
use App\Filament\App\Resources\Projects\Pages\EditProject;
use App\Filament\App\Resources\Projects\Pages\ListProjects;
use App\Filament\App\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\NationalReferentialsSeeder;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function actAsRole(Organization $org, string $role): User
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
    $this->seed(NationalReferentialsSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('crée un projet et affecte l’auteur comme chef de projet (RGP-13)', function (): void {
    $admin = actAsRole($this->org, 'admin');

    Livewire::test(CreateProject::class)
        ->fillForm([
            'title' => 'Projet WASH',
            'code' => 'WASH-01',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('code', 'WASH-01')->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::Brouillon)
        ->and($project->created_by)->toBe($admin->id)
        ->and($project->members()->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('applique une transition de statut valide et l’historise (RGP-06)', function (): void {
    actAsRole($this->org, 'admin');
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'status' => ProjectStatus::Brouillon]);

    Livewire::test(EditProject::class, ['record' => $project->getKey()])
        ->callAction('transition', data: ['to_status' => ProjectStatus::Valide->value]);

    expect($project->fresh()->status)->toBe(ProjectStatus::Valide)
        ->and($project->statusChanges()->where('to_status', ProjectStatus::Valide->value)->exists())->toBeTrue();
});

it('exige un motif pour suspendre (RGP-06)', function (): void {
    actAsRole($this->org, 'admin');
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'status' => ProjectStatus::EnCours]);

    Livewire::test(EditProject::class, ['record' => $project->getKey()])
        ->callAction('transition', data: ['to_status' => ProjectStatus::Suspendu->value])
        ->assertHasActionErrors(['reason']);

    expect($project->fresh()->status)->toBe(ProjectStatus::EnCours);
});

it('interdit l’accès de la ressource projet au rôle bailleur (RGP-16)', function (): void {
    actAsRole($this->org, 'bailleur');

    expect(ProjectResource::canAccess())->toBeFalse();
});

it('ne montre au chef de projet que les projets de son équipe (RGP-14)', function (): void {
    $chef = actAsRole($this->org, 'chef_projet');

    $mine = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'MINE']);
    ProjectMember::create(['project_id' => $mine->id, 'user_id' => $chef->id]);
    $other = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'OTHER']);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});
