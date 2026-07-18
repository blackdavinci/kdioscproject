<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Révoque immédiatement les sessions d'un compte (RG-10/11). Combiné au middleware
 * de fraîcheur, garantit qu'une désactivation ou expiration coupe la prochaine
 * interaction, sans attendre l'expiration de la session.
 */
class RevokeUserSessions
{
    public static function for(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }

        // Avec le driver Redis, la fraîcheur relue en base par le middleware
        // (ApplyTenantState) suffit à couper la session à la requête suivante.
    }
}
