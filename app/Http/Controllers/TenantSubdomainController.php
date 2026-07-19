<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

/**
 * Porte d'entrée « vitrine » par sous-domaine : ablogui.kidiani.com résout
 * l'organisation par son slug et redirige vers son espace sur l'hôte canonique
 * du panel (/app/{slug}). Le tenant reste isolé et l'accès est ensuite contrôlé
 * par l'authentification Filament. Les liens de travail profonds utilisent la
 * forme canonique /app/{slug}/… (toujours partageable, permanente).
 */
class TenantSubdomainController extends Controller
{
    /** Sous-domaines techniques qui ne désignent jamais une organisation. */
    private const RESERVED = ['app', 'www', 'admin', 'api', 'mail'];

    public function __invoke(string $osc): RedirectResponse
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        // Un sous-domaine réservé renvoie simplement vers l'application principale.
        if (in_array($osc, self::RESERVED, true)) {
            return redirect()->away($appUrl);
        }

        $organization = Organization::query()->where('slug', $osc)->first();

        abort_if($organization === null, 404);

        return redirect()->away($appUrl.'/app/'.$organization->slug);
    }
}
