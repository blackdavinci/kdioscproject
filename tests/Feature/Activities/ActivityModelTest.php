<?php

declare(strict_types=1);

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Organization;
use App\Support\DisaggregationCheck;
use App\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);
});

it('isole les activités par organisation (RG-02)', function (): void {
    $a = app(TenantContext::class)->runFor($this->orgA->id, fn () => Activity::factory()->create(['organization_id' => $this->orgA->id]));
    app(TenantContext::class)->runFor($this->orgB->id, fn () => Activity::factory()->create(['organization_id' => $this->orgB->id]));

    app(TenantContext::class)->set($this->orgA->id);

    expect(Activity::count())->toBe(1)
        ->and(Activity::sole()->is($a))->toBeTrue();
});

it('distingue la date de réalisation de la date de saisie (RGA-04)', function (): void {
    $activity = app(TenantContext::class)->runFor($this->orgA->id, fn () => Activity::factory()->realized()->create([
        'organization_id' => $this->orgA->id,
        'realized_at' => now()->subMonth()->toDateString(),
    ]));

    expect($activity->status)->toBe(ActivityStatus::Realisee)
        ->and($activity->realized_at->lessThan($activity->created_at))->toBeTrue();
});

it('valide la cohérence des désagrégations sexe/âge = total (RGA-05)', function (): void {
    $coherent = DisaggregationCheck::isCoherent(
        total: 10,
        sex: ['femme' => 6, 'homme' => 4],
        age: ['0_5' => 2, '6_14' => 3, '15_24' => 5, '25_59' => 0, '60_plus' => 0],
    );

    $issues = DisaggregationCheck::issues(
        total: 10,
        sex: ['femme' => 6, 'homme' => 3],
        age: ['15_24' => 10],
    );

    expect($coherent)->toBeTrue()
        ->and($issues)->toHaveCount(1)
        ->and($issues[0])->toContain('sexe');
});

it('lit le réglage enforce_disaggregation de l’organisation (RGA-05b)', function (): void {
    expect($this->orgA->enforcesDisaggregation())->toBeFalse();

    $this->orgA->update(['settings' => ['enforce_disaggregation' => true]]);

    expect($this->orgA->fresh()->enforcesDisaggregation())->toBeTrue();
});
