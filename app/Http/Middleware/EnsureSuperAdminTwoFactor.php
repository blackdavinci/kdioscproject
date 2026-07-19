<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impose la 2FA au super-admin (RG-09) sur le panel plateforme. Tant que la 2FA
 * n'est pas confirmée, l'accès est restreint à « Mon profil » (où il l'active).
 * On n'utilise pas le « force » de Breezy pour laisser la page de profil afficher
 * aussi le nom, le mot de passe et la photo — pas uniquement l'étape 2FA.
 */
class EnsureSuperAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof PlatformUser || $user->hasConfirmedTwoFactor()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        // Laisser passer la page de profil, le challenge 2FA et la déconnexion.
        foreach (['my-profile', 'two-factor', 'logout'] as $allowed) {
            if (str_contains($routeName, $allowed)) {
                return $next($request);
            }
        }

        $profileUrl = rtrim((string) Filament::getPanel('admin')->getUrl(), '/').'/my-profile';

        return redirect()->to($profileUrl)
            ->with('warning', 'La double authentification est obligatoire. Configurez-la pour continuer.');
    }
}
