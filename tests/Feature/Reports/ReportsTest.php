<?php

declare(strict_types=1);

use App\Enums\ActivityStatus;
use App\Filament\App\Pages\Reports;
use App\Models\Activity;
use App\Models\BudgetLine;
use App\Models\Indicator;
use App\Models\LogframeNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ResultFramework;
use App\Models\User;
use App\Support\Reports\ReportBuilder;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\PermissionRegistrar;

function bootReports(Organization $org, string $role): User
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

it('construit le rapport financier (budget vs dépensé par ligne) (RGR-03)', function (): void {
    app(TenantContext::class)->set($this->org->id);
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'P1']);
    BudgetLine::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id, 'amount_gnf' => 1_000_000]);

    $report = ReportBuilder::build('financial', ['project_id' => $project->id]);

    expect($report['title'])->toBe('Rapport financier')
        ->and($report['headings'])->toContain('Dépensé')
        ->and($report['rows'])->not->toBeEmpty()
        ->and($report['rows'][count($report['rows']) - 1][0])->toBe('TOTAL');
});

it('construit l’état des indicateurs d’un cadre (réalisé vs cible) (RGR-02)', function (): void {
    app(TenantContext::class)->set($this->org->id);
    $project = Project::factory()->create(['organization_id' => $this->org->id]);
    $indicator = Indicator::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id]);
    $indicator->targets()->create(['period_label' => '2026-T1', 'period_start' => now(), 'period_end' => now()->addMonths(3), 'target_value' => 100]);
    $indicator->values()->create(['period_label' => '2026-T1', 'period_start' => now(), 'period_end' => now()->addMonths(3), 'value' => 80]);
    $framework = ResultFramework::create(['organization_id' => $this->org->id, 'project_id' => $project->id, 'name' => 'Cadre UE']);
    $framework->indicators()->attach($indicator->id);

    $report = ReportBuilder::build('indicators', ['framework_id' => $framework->id]);

    expect($report['title'])->toBe('État des indicateurs')
        ->and($report['rows'])->toHaveCount(1)
        ->and($report['rows'][0][4])->toBe('80 %');
});

it('génère un rapport en Excel depuis la page Rapports (RGR-03)', function (): void {
    Excel::fake();
    bootReports($this->org, 'responsable_financier');
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'PX']);
    BudgetLine::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id]);

    Livewire::test(Reports::class)
        ->set('reportType', 'financial')
        ->set('projectId', $project->id)
        ->call('excel');

    Excel::assertDownloaded('rapport-financier-PX.xlsx');
});

it('génère un rapport d’activités en PDF (RGR-01)', function (): void {
    $me = bootReports($this->org, 'chef_projet');
    $project = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'PA']);
    $node = LogframeNode::factory()->create(['organization_id' => $this->org->id, 'project_id' => $project->id, 'type' => 'activite']);
    Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'logframe_node_id' => $node->id,
        'status' => ActivityStatus::Realisee,
        'realized_at' => now()->subDays(5),
        'responsible_user_id' => $me->id,
    ]);

    $response = Livewire::test(Reports::class)
        ->set('reportType', 'activities')
        ->set('projectId', $project->id)
        ->set('periodStart', now()->subMonth()->toDateString())
        ->set('periodEnd', now()->toDateString())
        ->call('pdf');

    $response->assertFileDownloaded('rapport-activites-PA.pdf');
});

it('réserve la page Rapports, jamais au bailleur', function (): void {
    bootReports($this->org, 'chef_projet');
    expect(Reports::canAccess())->toBeTrue();

    bootReports($this->org, 'bailleur');
    expect(Reports::canAccess())->toBeFalse();
});
