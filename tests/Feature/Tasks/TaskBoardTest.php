<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Filament\App\Pages\MyTasks;
use App\Filament\App\Pages\TaskBoard;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootBoard(Organization $org, string $role): User
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

it('déplace une tâche au kanban : change statut et position (RGT-06)', function (): void {
    bootBoard($this->org, 'admin');
    $task = Task::factory()->create(['organization_id' => $this->org->id, 'status' => TaskStatus::AFaire]);

    Livewire::test(TaskBoard::class)->call('moveTask', $task->id, TaskStatus::EnCours->value, 2);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::EnCours)
        ->and($task->position)->toBe(2);
});

it('horodate la clôture au passage en « terminé » via le kanban', function (): void {
    bootBoard($this->org, 'admin');
    $task = Task::factory()->create(['organization_id' => $this->org->id, 'status' => TaskStatus::EnCours]);

    Livewire::test(TaskBoard::class)->call('moveTask', $task->id, TaskStatus::Termine->value, 0);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('refuse le déplacement d’une tâche d’un projet en lecture seule (RGP-07)', function (): void {
    bootBoard($this->org, 'admin');
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'status' => ProjectStatus::Cloture]);
    $task = Task::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'status' => TaskStatus::AFaire,
    ]);

    Livewire::test(TaskBoard::class)->call('moveTask', $task->id, TaskStatus::EnCours->value, 0);

    expect($task->fresh()->status)->toBe(TaskStatus::AFaire);
});

it('liste mes tâches non terminées triées par échéance (RGT-07)', function (): void {
    $me = bootBoard($this->org, 'chef_projet');
    $soon = Task::factory()->create(['organization_id' => $this->org->id, 'assignee_user_id' => $me->id, 'due_date' => now()->addDay(), 'status' => TaskStatus::AFaire]);
    Task::factory()->create(['organization_id' => $this->org->id, 'assignee_user_id' => $me->id, 'status' => TaskStatus::Termine]);
    $other = Task::factory()->create(['organization_id' => $this->org->id, 'status' => TaskStatus::AFaire]);

    Livewire::test(MyTasks::class)
        ->assertCanSeeTableRecords([$soon])
        ->assertCanNotSeeTableRecords([$other]);
});
