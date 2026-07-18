<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Sector;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;

it('crée un compte cohérent avec sa fiche membre dans la même organisation (RG-17)', function (): void {
    $user = User::factory()->create();

    expect($user->organization_id)->not->toBeNull()
        ->and($user->team_member_id)->not->toBeNull()
        ->and($user->teamMember)->toBeInstanceOf(TeamMember::class)
        ->and($user->teamMember->organization_id)->toBe($user->organization_id)
        ->and($user->getFilamentName())->toBe($user->teamMember->full_name);
});

it('expose les secteurs nationaux ET ceux de l’organisation courante, jamais ceux d’une autre (RG-19)', function (): void {
    // Secteur national (hors contexte → organization_id NULL).
    app(TenantContext::class)->forget();
    Sector::create(['name' => 'Santé']);

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    app(TenantContext::class)->runFor($orgA->id, fn () => Sector::create(['name' => 'Secteur propre A']));
    app(TenantContext::class)->runFor($orgB->id, fn () => Sector::create(['name' => 'Secteur propre B']));

    app(TenantContext::class)->set($orgA->id);

    expect(Sector::pluck('name')->all())
        ->toEqualCanonicalizing(['Santé', 'Secteur propre A']);
});
