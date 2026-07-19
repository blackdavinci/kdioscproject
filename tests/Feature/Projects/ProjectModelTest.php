<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);
});

it('isole les projets par organisation (RG-02)', function (): void {
    $a = app(TenantContext::class)->runFor($this->orgA->id, fn () => Project::factory()->create(['organization_id' => $this->orgA->id, 'code' => 'P-A']));
    $b = app(TenantContext::class)->runFor($this->orgB->id, fn () => Project::factory()->create(['organization_id' => $this->orgB->id, 'code' => 'P-B']));

    app(TenantContext::class)->set($this->orgA->id);

    expect(Project::count())->toBe(1)
        ->and(Project::sole()->is($a))->toBeTrue()
        ->and(Project::find($b->id))->toBeNull();
});

it('renseigne automatiquement organization_id depuis le contexte courant', function (): void {
    $project = app(TenantContext::class)->runFor($this->orgA->id, fn () => Project::create([
        'code' => 'AUTO-1',
        'title' => 'Projet auto',
        'start_date' => now(),
        'end_date' => now()->addYear(),
    ]));

    expect($project->organization_id)->toBe($this->orgA->id);
});

it('définit correctement les transitions de statut autorisées (RGP-05)', function (): void {
    expect(ProjectStatus::Brouillon->canTransitionTo(ProjectStatus::Valide))->toBeTrue()
        ->and(ProjectStatus::Brouillon->canTransitionTo(ProjectStatus::EnCours))->toBeFalse()
        ->and(ProjectStatus::EnCours->canTransitionTo(ProjectStatus::Cloture))->toBeTrue()
        ->and(ProjectStatus::EnCours->canTransitionTo(ProjectStatus::Suspendu))->toBeTrue()
        ->and(ProjectStatus::Suspendu->canTransitionTo(ProjectStatus::EnCours))->toBeTrue()
        ->and(ProjectStatus::Cloture->allowedTransitions())->toBe([])
        ->and(ProjectStatus::Cloture->canTransitionTo(ProjectStatus::EnCours))->toBeFalse();
});

it('marque suspendu et clôturé en lecture seule et exige un motif (RGP-06/07)', function (): void {
    expect(ProjectStatus::Suspendu->isReadOnly())->toBeTrue()
        ->and(ProjectStatus::Cloture->isReadOnly())->toBeTrue()
        ->and(ProjectStatus::EnCours->isReadOnly())->toBeFalse()
        ->and(ProjectStatus::Suspendu->requiresReason())->toBeTrue()
        ->and(ProjectStatus::Cloture->requiresReason())->toBeTrue()
        ->and(ProjectStatus::Valide->requiresReason())->toBeFalse();
});

it('expose les rôles projet nationaux et propres à l’organisation, jamais ceux d’une autre (RGP-12)', function (): void {
    $national = ProjectRole::create(['organization_id' => null, 'name' => 'Chef de projet']);
    $ownA = app(TenantContext::class)->runFor($this->orgA->id, fn () => ProjectRole::create(['name' => 'Rôle Alpha']));
    $ownB = app(TenantContext::class)->runFor($this->orgB->id, fn () => ProjectRole::create(['name' => 'Rôle Beta']));

    app(TenantContext::class)->set($this->orgA->id);

    $visible = ProjectRole::pluck('id');

    expect($visible)->toContain($national->id)
        ->and($visible)->toContain($ownA->id)
        ->and($visible)->not->toContain($ownB->id);
});
