<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impose la 2FA aux administrateurs d'organisation (RG-09) sur le panel tenant : tant
 * qu'un admin n'a pas confirmé sa 2FA, il est redirigé vers « Mon profil » pour la
 * configurer. Les autres rôles ne sont pas contraints (2FA proposée). La 2FA du
 * super-admin est, elle, imposée à tous via le force de Breezy sur le panel admin.
 */
class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->hasRole('admin') || $user->hasConfirmedTwoFactor()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        // Laisser passer la page de profil, le challenge 2FA et la déconnexion.
        foreach (['my-profile', 'two-factor', 'logout'] as $allowed) {
            if (str_contains($routeName, $allowed)) {
                return $next($request);
            }
        }

        $tenant = Filament::getTenant();
        $profileUrl = Filament::getPanel('app')->getUrl($tenant);

        return redirect()->to(rtrim((string) $profileUrl, '/').'/my-profile')
            ->with('warning', 'La double authentification est obligatoire pour les administrateurs. Configurez-la pour continuer.');
    }
}
