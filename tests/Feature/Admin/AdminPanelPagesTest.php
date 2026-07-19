<?php

declare(strict_types=1);

use App\Models\PlatformUser;

beforeEach(function (): void {
    $superAdmin = PlatformUser::factory()->create();
    $session = confirmTwoFactor($superAdmin, 'admin');
    $this->actingAs($superAdmin, 'platform')->withSession(validTwoFactorSession($session));
});

it('affiche l’écran d’import du référentiel géographique au super-admin', function (): void {
    $this->get('/admin/geo-referential')->assertOk()->assertSee('Import du référentiel géographique national');
});

it('affiche les pages Santé et Sauvegardes au super-admin (§5 écran 10)', function (): void {
    $this->get('/admin/health-check-results')->assertOk();
    $this->get('/admin/backups')->assertOk();
});
