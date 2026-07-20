<?php

declare(strict_types=1);

use App\Enums\ExpenseKind;
use App\Filament\App\Pages\BudgetTracking;
use App\Filament\App\Resources\BudgetLines\BudgetLineResource;
use App\Filament\App\Resources\Expenses\ExpenseResource;
use App\Filament\App\Resources\Expenses\Pages\CreateExpense;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\PermissionRegistrar;

function bootBudget(Organization $org, string $role): User
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

it('saisit une dépense rattachée à une ligne budgétaire (RGB-04)', function (): void {
    $me = bootBudget($this->org, 'responsable_financier');
    $project = Project::factory()->create(['organization_id' => $this->org->id]);
    $line = BudgetLine::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id, 'amount_gnf' => 1_000_000]);

    Livewire::test(CreateExpense::class)
        ->fillForm([
            'project_id' => $project->id,
            'budget_line_id' => $line->id,
            'kind' => ExpenseKind::Realisee->value,
            'label' => 'Achat de fournitures',
            'amount_gnf' => 300_000,
            'spent_on' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $line->refresh();
    expect($line->spent())->toBe(300_000)
        ->and($line->expenses()->first()->recorded_by)->toBe($me->id);
});

it('exporte l’état budgétaire d’un projet en Excel (RGB-08)', function (): void {
    Excel::fake();
    bootBudget($this->org, 'responsable_financier');
    $project = Project::factory()->create(['organization_id' => $this->org->id]);
    BudgetLine::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id]);

    Livewire::test(BudgetTracking::class)
        ->set('projectId', $project->id)
        ->call('export');

    Excel::assertDownloaded('etat-budgetaire-'.$project->id.'.xlsx');
});

it('réserve la gestion budgétaire, jamais au bailleur', function (): void {
    bootBudget($this->org, 'responsable_financier');
    expect(BudgetLineResource::canAccess())->toBeTrue()
        ->and(ExpenseResource::canAccess())->toBeTrue();

    bootBudget($this->org, 'bailleur');
    expect(BudgetLineResource::canAccess())->toBeFalse()
        ->and(ExpenseResource::canAccess())->toBeFalse();

    bootBudget($this->org, 'agent_terrain');
    expect(ExpenseResource::canAccess())->toBeFalse();
});
