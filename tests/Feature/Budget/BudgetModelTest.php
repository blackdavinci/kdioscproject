<?php

declare(strict_types=1);

use App\Models\BudgetLine;
use App\Models\Expense;
use App\Models\Organization;
use App\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);
});

it('isole les lignes budgétaires par organisation (RG-02)', function (): void {
    $a = app(TenantContext::class)->runFor($this->orgA->id, fn () => BudgetLine::factory()->create(['organization_id' => $this->orgA->id]));
    app(TenantContext::class)->runFor($this->orgB->id, fn () => BudgetLine::factory()->create(['organization_id' => $this->orgB->id]));

    app(TenantContext::class)->set($this->orgA->id);

    expect(BudgetLine::count())->toBe(1)
        ->and(BudgetLine::sole()->is($a))->toBeTrue();
});

it('calcule budget / engagé / dépensé / disponible et le taux (RGB-05/06)', function (): void {
    $line = app(TenantContext::class)->runFor($this->orgA->id, function (): BudgetLine {
        $line = BudgetLine::factory()->create(['organization_id' => $this->orgA->id, 'amount_gnf' => 1_000_000, 'threshold_percent' => 80]);
        Expense::factory()->create(['organization_id' => $this->orgA->id, 'project_id' => $line->project_id, 'budget_line_id' => $line->id, 'kind' => 'realisee', 'amount_gnf' => 600_000]);
        Expense::factory()->commitment()->create(['organization_id' => $this->orgA->id, 'project_id' => $line->project_id, 'budget_line_id' => $line->id, 'amount_gnf' => 200_000]);

        return $line;
    });

    expect($line->spent())->toBe(600_000)
        ->and($line->committed())->toBe(200_000)
        ->and($line->available())->toBe(200_000)
        ->and($line->consumptionRate())->toBe(0.6)
        ->and($line->isOverThreshold())->toBeFalse();
});

it('détecte le dépassement de seuil et le dépassement de budget (RGB-07)', function (): void {
    $line = app(TenantContext::class)->runFor($this->orgA->id, function (): BudgetLine {
        $line = BudgetLine::factory()->create(['organization_id' => $this->orgA->id, 'amount_gnf' => 1_000_000, 'threshold_percent' => 80]);
        Expense::factory()->create(['organization_id' => $this->orgA->id, 'project_id' => $line->project_id, 'budget_line_id' => $line->id, 'kind' => 'realisee', 'amount_gnf' => 1_100_000]);

        return $line;
    });

    expect($line->isOverThreshold())->toBeTrue()
        ->and($line->isOverspent())->toBeTrue();
});
