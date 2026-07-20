<?php

declare(strict_types=1);

use App\Actions\Comments\PostComment;
use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Filament\App\Resources\Tasks\Pages\CreateTask;
use App\Filament\App\Resources\Tasks\Support\CompleteRecurringTask;
use App\Filament\App\Resources\Tasks\TaskResource;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentMentionMail;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootTask(Organization $org, string $role): User
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

it('crée une tâche interne (hors projet) assignée (RGT-01/04)', function (): void {
    $me = bootTask($this->org, 'chef_projet');

    Livewire::test(CreateTask::class)
        ->fillForm([
            'title' => 'Renouveler l’agrément',
            'priority' => 'haute',
            'status' => 'a_faire',
            'assignee_user_id' => $me->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::where('title', 'Renouveler l’agrément')->firstOrFail();
    expect($task->isInternal())->toBeTrue()
        ->and($task->created_by)->toBe($me->id);
});

it('publie un commentaire et ne notifie que des mentions de l’organisation (RGT-09)', function (): void {
    Notification::fake();

    $author = bootTask($this->org, 'chef_projet');
    $colleague = User::factory()->create(['organization_id' => $this->org->id]);
    $otherOrg = Organization::factory()->create();
    $stranger = User::factory()->create(['organization_id' => $otherOrg->id]);

    $task = Task::factory()->create(['organization_id' => $this->org->id]);

    $comment = (new PostComment)->handle($task, $author, 'Bon travail !', [$colleague->id, $stranger->id]);

    expect($comment->mentions()->count())->toBe(1)
        ->and($comment->mentions()->first()->user_id)->toBe($colleague->id);

    Notification::assertSentTo($colleague, CommentMentionMail::class);
    Notification::assertNotSentTo($stranger, CommentMentionMail::class);
});

it('trace l’édition d’un commentaire (RGT-08)', function (): void {
    $author = bootTask($this->org, 'chef_projet');
    $task = Task::factory()->create(['organization_id' => $this->org->id]);
    $comment = Comment::factory()->create([
        'organization_id' => $this->org->id,
        'commentable_type' => $task->getMorphClass(),
        'commentable_id' => $task->id,
        'user_id' => $author->id,
    ]);

    $comment->update(['body' => 'corrigé', 'edited_at' => now()]);

    expect($comment->fresh()->isEdited())->toBeTrue();
});

it('génère l’occurrence suivante d’une tâche récurrente à la clôture (RGT-13)', function (): void {
    bootTask($this->org, 'chef_projet');
    $task = Task::factory()->create([
        'organization_id' => $this->org->id,
        'due_date' => now()->toDateString(),
        'recurrence' => TaskRecurrence::Mensuelle,
        'status' => TaskStatus::Termine,
    ]);

    $next = CompleteRecurringTask::spawnNext($task);
    $task->refresh();

    expect($next)->not->toBeNull()
        ->and($next->status)->toBe(TaskStatus::AFaire)
        ->and($next->recurrence_group_id)->toBe($task->recurrence_group_id)
        ->and($next->due_date->toDateString())->toBe($task->due_date->copy()->addMonthNoOverflow()->toDateString());
});

it('interdit l’accès des tâches au rôle bailleur', function (): void {
    bootTask($this->org, 'bailleur');

    expect(TaskResource::canAccess())->toBeFalse();
});
