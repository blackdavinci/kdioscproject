<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware du panel tenant `app`, exécuté après résolution de l'organisation.
 *
 * 1. établit le contexte d'isolation (global scope) et l'équipe spatie/permission ;
 * 2. applique la fraîcheur du contrôle d'accès (RG-04, RG-10) : statut du compte,
 *    expiration et suspension de l'organisation sont relus depuis la base à chaque
 *    requête — jamais depuis la seule session (indispensable en worker FrankenPHP).
 *    Toute anomalie déconnecte immédiatement avec un message explicite.
 */
class ApplyTenantState
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();
        $user = $request->user();

        if (! $tenant instanceof Organization || ! $user instanceof User) {
            return $next($request);
        }

        // Contexte d'isolation + équipe spatie (avant toute vérification de rôle).
        app(TenantContext::class)->set($tenant->getKey());
        setPermissionsTeamId($tenant->getKey());

        // Relecture fraîche en base (hors global scope pour retrouver le compte).
        /** @var User|null $fresh */
        $fresh = User::withoutGlobalScopes()->whereKey($user->getKey())->first();

        // RG-04 : organisation suspendue → connexion bloquée.
        if (! $tenant->isActive()) {
            return $this->reject($request, __('tenancy.organization_suspended'));
        }

        // RG-10 : expiration atteinte → bascule en `expired` et coupe la session.
        if ($fresh !== null && $fresh->hasExpired() && $fresh->status !== UserStatus::Expired) {
            $fresh->forceFill(['status' => UserStatus::Expired])->save();
        }

        // RG-10/11 : tout état non actif (désactivé, expiré) coupe la prochaine interaction.
        if ($fresh === null || ! $fresh->isActive()) {
            return $this->reject($request, __('tenancy.account_inactive'));
        }

        return $next($request);
    }

    /**
     * Déconnecte et révoque la session, puis renvoie vers la connexion avec message.
     */
    protected function reject(Request $request, string $message): Response
    {
        Filament::auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->to((string) Filament::getPanel('app')->getLoginUrl())
            ->with('error', $message);
    }
}
