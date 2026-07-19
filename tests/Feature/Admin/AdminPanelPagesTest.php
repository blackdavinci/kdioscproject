<?php

declare(strict_types=1);

use App\Models\PlatformUser;

beforeEach(function (): void {
    $this->actingAs(PlatformUser::factory()->create(), 'platform');
});

it('affiche le référentiel géographique au super-admin', function (): void {
    $this->get('/admin/geo-referential')->assertOk()->assertSee('Référentiel géographique national');
});

it('affiche les pages Santé et Sauvegardes au super-admin (§5 écran 10)', function (): void {
    $this->get('/admin/health-check-results')->assertOk();
    $this->get('/admin/backups')->assertOk();
});
