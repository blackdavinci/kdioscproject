<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jeffgreco13\FilamentBreezy\Models\BreezySession;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cas de test
|--------------------------------------------------------------------------
|
| Les tests de fonctionnalité étendent Tests\TestCase et rafraîchissent la base
| à chaque test. Les tests unitaires restent légers (PHPUnit brut).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations personnalisées
|--------------------------------------------------------------------------
*/

expect()->extend('toBeUlid', function () {
    expect($this->value)->toBeString()->toHaveLength(26);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Marque la 2FA d'un compte comme confirmée (RG-09), pour les tests d'écrans
 * derrière l'imposition 2FA du super-admin.
 */
function confirmTwoFactor(Model $user, string $panelId = 'app'): BreezySession
{
    return BreezySession::create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'panel_id' => $panelId,
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);
}

/**
 * Variable de session marquant la 2FA validée pour la requête (RG-09).
 *
 * @return array<string, string>
 */
function validTwoFactorSession(BreezySession $session): array
{
    return ['breezy_session_id' => md5((string) $session->id)];
}
