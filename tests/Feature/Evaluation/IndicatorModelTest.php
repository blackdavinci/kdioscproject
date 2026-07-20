<?php

declare(strict_types=1);

use App\Enums\IndicatorDirection;
use App\Models\Beneficiary;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ResultFramework;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);
});

it('isole les indicateurs par organisation (RG-02)', function (): void {
    $a = app(TenantContext::class)->runFor($this->orgA->id, fn () => Indicator::factory()->create(['organization_id' => $this->orgA->id]));
    app(TenantContext::class)->runFor($this->orgB->id, fn () => Indicator::factory()->create(['organization_id' => $this->orgB->id]));

    app(TenantContext::class)->set($this->orgA->id);

    expect(Indicator::count())->toBe(1)
        ->and(Indicator::sole()->is($a))->toBeTrue();
});

it('calcule le taux d’atteinte selon le sens de l’indicateur (RGSE-07)', function (): void {
    expect(IndicatorDirection::Croissant->attainment(80, 100))->toBe(0.8)
        ->and(IndicatorDirection::Decroissant->attainment(80, 100))->toBe(1.25)
        ->and(IndicatorDirection::Croissant->attainment(50, 0.0))->toBeNull()
        ->and(IndicatorDirection::Croissant->attainment(50, null))->toBeNull();
});

it('chiffre les nominatifs des bénéficiaires au repos (RGSE-09)', function (): void {
    $beneficiary = app(TenantContext::class)->runFor($this->orgA->id, fn () => Beneficiary::factory()->create([
        'organization_id' => $this->orgA->id,
        'full_name' => 'Aissatou Diallo',
        'contact' => '+224620000000',
    ]));

    // Lecture applicative : déchiffré.
    expect($beneficiary->fresh()->full_name)->toBe('Aissatou Diallo');

    // En base : jamais en clair.
    $raw = DB::table('beneficiaries')->where('id', $beneficiary->id)->value('full_name');
    expect($raw)->not->toContain('Aissatou');
});

it('rattache un indicateur à plusieurs cadres de résultats sans duplication (RGSE-08)', function (): void {
    app(TenantContext::class)->set($this->orgA->id);
    $project = Project::factory()->create(['organization_id' => $this->orgA->id]);
    $indicator = Indicator::factory()->create(['organization_id' => $this->orgA->id, 'project_id' => $project->id]);

    $f1 = ResultFramework::create(['organization_id' => $this->orgA->id, 'project_id' => $project->id, 'name' => 'Cadre UE']);
    $f2 = ResultFramework::create(['organization_id' => $this->orgA->id, 'project_id' => $project->id, 'name' => 'Cadre UNICEF']);
    $indicator->frameworks()->attach([$f1->id, $f2->id]);

    expect($indicator->frameworks()->count())->toBe(2)
        ->and($f1->indicators()->first()->is($indicator))->toBeTrue();
});
