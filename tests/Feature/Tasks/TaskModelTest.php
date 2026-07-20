<?php

declare(strict_types=1);

use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);
});

it('isole les tâches par organisation (RG-02)', function (): void {
    $a = app(TenantContext::class)->runFor($this->orgA->id, fn () => Task::factory()->create(['organization_id' => $this->orgA->id]));
    app(TenantContext::class)->runFor($this->orgB->id, fn () => Task::factory()->create(['organization_id' => $this->orgB->id]));

    app(TenantContext::class)->set($this->orgA->id);

    expect(Task::count())->toBe(1)
        ->and(Task::sole()->is($a))->toBeTrue();
});

it('distingue une tâche interne d’une tâche de projet (RGT-04)', function (): void {
    $internal = app(TenantContext::class)->runFor($this->orgA->id, fn () => Task::factory()->create(['organization_id' => $this->orgA->id]));

    expect($internal->isInternal())->toBeTrue();
});

it('détecte une tâche en retard (échéance passée, non terminée)', function (): void {
    $late = app(TenantContext::class)->runFor($this->orgA->id, fn () => Task::factory()->create([
        'organization_id' => $this->orgA->id,
        'due_date' => now()->subDay(),
        'status' => TaskStatus::EnCours,
    ]));
    $done = app(TenantContext::class)->runFor($this->orgA->id, fn () => Task::factory()->create([
        'organization_id' => $this->orgA->id,
        'due_date' => now()->subDay(),
        'status' => TaskStatus::Termine,
    ]));

    expect($late->isOverdue())->toBeTrue()
        ->and($done->isOverdue())->toBeFalse();
});

it('calcule la prochaine échéance d’une récurrence (RGT-13)', function (): void {
    $base = now()->startOfDay();

    expect(TaskRecurrence::Mensuelle->next($base)->toDateString())->toBe($base->copy()->addMonthNoOverflow()->toDateString())
        ->and(TaskRecurrence::Trimestrielle->next($base)->toDateString())->toBe($base->copy()->addMonthsNoOverflow(3)->toDateString())
        ->and(TaskRecurrence::Annuelle->isRecurring())->toBeTrue()
        ->and(TaskRecurrence::Aucune->isRecurring())->toBeFalse();
});

it('attache un commentaire polymorphe isolé à une tâche (RGT-08)', function (): void {
    app(TenantContext::class)->set($this->orgA->id);
    $task = Task::factory()->create(['organization_id' => $this->orgA->id]);
    $comment = Comment::factory()->create([
        'organization_id' => $this->orgA->id,
        'commentable_type' => $task->getMorphClass(),
        'commentable_id' => $task->id,
    ]);

    expect($task->comments()->count())->toBe(1)
        ->and($comment->commentable->is($task))->toBeTrue();
});
