<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Portée du tableau de bord (RGD-01) : l'admin et le responsable S&E voient toute
 * l'organisation ; les autres rôles voient les projets de leur équipe.
 */
class DashboardScope
{
    /**
     * Identifiants des projets visibles par l'utilisateur courant.
     *
     * @return Collection<int, string>
     */
    public static function visibleProjectIds(): Collection
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        $query = Project::query();

        if (! $user->hasAnyRole(['admin', 'responsable_se', 'responsable_financier'])) {
            $query->whereHas('members', fn (Builder $m): Builder => $m->where('user_id', $user->id));
        }

        return $query->pluck('id');
    }

    public static function seesWholeOrganization(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se', 'responsable_financier']);
    }
}
