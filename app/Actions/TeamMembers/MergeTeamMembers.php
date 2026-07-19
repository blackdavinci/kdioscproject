<?php

declare(strict_types=1);

namespace App\Actions\TeamMembers;

use App\Models\Invitation;
use App\Models\TeamMember;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fusionne une fiche membre en doublon (source) dans une fiche cible (RG-16) : réassigne
 * toutes les références de la source vers la cible, archive la source, et renvoie le
 * décompte d'objets réassignés (pour la confirmation). Opération journalisée (audit).
 *
 * Portée socle : les invitations. Extensible aux tâches/activités/réalisations des
 * modules à venir sans changer l'appelant.
 */
class MergeTeamMembers
{
    public function handle(TeamMember $source, TeamMember $target): int
    {
        if ($source->getKey() === $target->getKey()) {
            throw new RuntimeException('Impossible de fusionner une fiche avec elle-même.');
        }

        if ($source->organization_id !== $target->organization_id) {
            throw new RuntimeException('Les deux fiches doivent appartenir à la même organisation.');
        }

        if ($source->user_id !== null) {
            throw new RuntimeException('Une fiche rattachée à un compte ne peut pas être fusionnée.');
        }

        return DB::transaction(function () use ($source, $target): int {
            app(TenantContext::class)->set($source->organization_id);

            $reassigned = Invitation::query()
                ->where('team_member_id', $source->getKey())
                ->update(['team_member_id' => $target->getKey()]);

            // Archive la source (soft delete), historique conservé.
            $source->delete();

            activity()
                ->performedOn($target)
                ->withProperties([
                    'merged_from' => $source->getKey(),
                    'reassigned_count' => $reassigned,
                ])
                ->event('team_members_merged')
                ->log('Fusion de fiches membres');

            return $reassigned;
        });
    }
}
