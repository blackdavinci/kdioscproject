<?php

declare(strict_types=1);

use App\Actions\Tasks\RemindDueTasks;
use App\Actions\Tasks\SendOverdueTasksDigest;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('rappelle l’échéance à l’assigné au bon jour J-X (RGT-13)', function (): void {
    Notification::fake();

    $assignee = User::factory()->create(['organization_id' => $this->org->id]);
    app(TenantContext::class)->runFor($this->org->id, function () use ($assignee): void {
        // Échéance dans 7 jours, rappel à J-7 => aujourd'hui.
        Task::factory()->create([
            'organization_id' => $this->org->id,
            'assignee_user_id' => $assignee->id,
            'due_date' => now()->addDays(7),
            'reminder_days_before' => 7,
            'status' => TaskStatus::AFaire,
        ]);
        // Ne doit pas déclencher (rappel à J-3, échéance dans 10 jours).
        Task::factory()->create([
            'organization_id' => $this->org->id,
            'assignee_user_id' => $assignee->id,
            'due_date' => now()->addDays(10),
            'reminder_days_before' => 3,
            'status' => TaskStatus::AFaire,
        ]);
    });

    $count = (new RemindDueTasks)->handle();

    expect($count)->toBe(1);
    Notification::assertSentTo($assignee, TaskMailNotice::class);
});

it('notifie les membres du projet des tâches en retard (RGT-14)', function (): void {
    Notification::fake();

    $chef = User::factory()->create(['organization_id' => $this->org->id]);
    $project = app(TenantContext::class)->runFor($this->org->id, function () use ($chef): Project {
        $p = Project::factory()->create(['organization_id' => $this->org->id]);
        ProjectMember::create(['project_id' => $p->id, 'user_id' => $chef->id]);
        Task::factory()->create([
            'organization_id' => $this->org->id,
            'project_id' => $p->id,
            'due_date' => now()->subDays(3),
            'status' => TaskStatus::EnCours,
        ]);

        return $p;
    });

    $notified = (new SendOverdueTasksDigest)->handle();

    expect($notified)->toBeGreaterThanOrEqual(1);
    Notification::assertSentTo($chef, TaskMailNotice::class);
});
