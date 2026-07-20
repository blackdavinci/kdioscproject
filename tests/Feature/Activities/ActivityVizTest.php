<?php

declare(strict_types=1);

use App\Filament\App\Pages\ActivityCalendar;
use App\Filament\App\Pages\ProjectGantt;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $admin = User::factory()->create(['organization_id' => $this->org->id]);
    $admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($admin);
    Filament::setTenant($this->org);
    app(TenantContext::class)->set($this->org->id);
});

it('positionne les activités d’un projet sur le chronogramme (story 2.5)', function (): void {
    $project = Project::factory()->create([
        'organization_id' => $this->org->id,
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->addMonths(2),
    ]);
    Activity::factory()->count(2)->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'planned_start' => now()->addDays(5),
    ]);

    $component = Livewire::test(ProjectGantt::class);
    $component->set('projectId', $project->id);

    $data = $component->instance()->gantt();

    expect($data)->not->toBeNull()
        ->and($data['bars'])->toHaveCount(2);
});

it('construit une grille mensuelle complète (story 3.5)', function (): void {
    $weeks = Livewire::test(ActivityCalendar::class)->instance()->weeks();

    expect(count($weeks))->toBeGreaterThanOrEqual(4)
        ->and($weeks[0])->toHaveCount(7);
});
