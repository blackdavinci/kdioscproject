<?php

declare(strict_types=1);

use App\Actions\Budget\AlertBudgetThresholds;
use App\Actions\Tasks\NotifyTaskAssignment;
use App\Enums\ProjectStatus;
use App\Filament\App\Widgets\OverviewStats;
use App\Models\BudgetLine;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use App\Support\DashboardScope;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootDash(Organization $org, string $role): User
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

it('scope le tableau de bord à l’organisation pour l’admin (RGD-01)', function (): void {
    bootDash($this->org, 'admin');
    Project::factory()->create(['organization_id' => $this->org->id, 'status' => ProjectStatus::EnCours]);

    expect(DashboardScope::seesWholeOrganization())->toBeTrue()
        ->and(DashboardScope::visibleProjectIds())->toHaveCount(1);

    Livewire::test(OverviewStats::class)->assertOk();
});

it('limite le tableau de bord du chef de projet à ses projets (RGD-01)', function (): void {
    $chef = bootDash($this->org, 'chef_projet');
    $mine = Project::factory()->create(['organization_id' => $this->org->id]);
    ProjectMember::create(['project_id' => $mine->id, 'user_id' => $chef->id]);
    Project::factory()->create(['organization_id' => $this->org->id]); // autre équipe

    expect(DashboardScope::seesWholeOrganization())->toBeFalse()
        ->and(DashboardScope::visibleProjectIds()->all())->toBe([$mine->id]);
});

it('notifie les responsables financiers d’une ligne au-dessus du seuil, une seule fois (RGD-06)', function (): void {
    Notification::fake();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $financier = User::factory()->create(['organization_id' => $this->org->id]);
    $financier->assignRole('responsable_financier');

    $line = app(TenantContext::class)->runFor($this->org->id, function (): BudgetLine {
        $line = BudgetLine::factory()->create(['organization_id' => $this->org->id, 'amount_gnf' => 1_000_000, 'threshold_percent' => 80]);
        Expense::factory()->create(['organization_id' => $this->org->id, 'project_id' => $line->project_id, 'budget_line_id' => $line->id, 'kind' => 'realisee', 'amount_gnf' => 900_000]);

        return $line;
    });

    expect((new AlertBudgetThresholds)->handle())->toBe(1);
    Notification::assertSentTo($financier, TaskMailNotice::class);
    expect($line->fresh()->alert_notified_at)->not->toBeNull();

    // Deuxième passage : aucune nouvelle alerte (anti-doublon).
    expect((new AlertBudgetThresholds)->handle())->toBe(0);
});

it('notifie l’assigné d’une tâche (RGD-07)', function (): void {
    Notification::fake();
    bootDash($this->org, 'chef_projet');
    $assignee = User::factory()->create(['organization_id' => $this->org->id]);
    $task = Task::factory()->create(['organization_id' => $this->org->id, 'assignee_user_id' => $assignee->id]);

    NotifyTaskAssignment::notify($task);

    Notification::assertSentTo($assignee, TaskMailNotice::class);
});
