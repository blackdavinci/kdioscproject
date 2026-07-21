<?php

declare(strict_types=1);

use App\Models\BudgetLine;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\NationalReferentialsSeeder;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Smoke test : chaque page du panel OSC se charge sans erreur (200) pour un
| admin, avec un jeu de données minimal. Détecte les erreurs de rendu (500).
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    // On teste le rendu des pages, pas la 2FA : désactiver le blocage 2FA admin.
    config(['kdiosc.enforce_admin_two_factor' => false]);

    $this->seed(RolesSeeder::class);
    $this->seed(NationalReferentialsSeeder::class);

    $this->org = Organization::factory()->create(['name' => 'ABLOGUI', 'slug' => 'ablogui']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $this->admin = User::factory()->create(['organization_id' => $this->org->id, 'email' => 'admin@ablogui.test']);
    $this->admin->assignRole('admin');

    app(TenantContext::class)->set($this->org->id);

    // Données minimales pour exercer les pages (projet + indicateur + ligne budgétaire).
    $project = Project::factory()->create(['organization_id' => $this->org->id]);
    Indicator::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id]);
    BudgetLine::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id]);

    Filament::setCurrentPanel(Filament::getPanel('app'));
});

$pages = [
    '', 'projects', 'projects/create', 'portfolio',
    'activities', 'activities/create', 'project-gantt', 'activity-calendar', 'interventions-map',
    'task-board', 'my-tasks', 'tasks', 'tasks/create',
    'indicators', 'indicators/create', 'indicator-progress', 'result-frameworks', 'result-frameworks/create',
    'beneficiaries', 'beneficiaries/create', 'reports',
    'budget-lines', 'budget-lines/create', 'expenses', 'expenses/create', 'budget-tracking',
    'tags', 'sectors', 'donors', 'users', 'team-members', 'localities', 'audit-logs', 'billing',
];

it('charge la page OSC sans erreur', function (string $path): void {
    $url = '/app/'.$this->org->slug.($path === '' ? '' : '/'.$path);

    $this->actingAs($this->admin)->get($url)->assertSuccessful();
})->with($pages);
