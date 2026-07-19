<?php

declare(strict_types=1);

namespace App\Actions\Assistance;

use App\Models\AssistanceSession;

/**
 * Clôt un accès d'assistance (RG-14), tracé à la clôture avec l'opérateur comme auteur.
 */
class EndAssistanceAccess
{
    public function handle(AssistanceSession $session): void
    {
        if ($session->ended_at !== null) {
            return;
        }

        $session->forceFill(['ended_at' => now()])->save();

        $organization = $session->organization()->first();

        if ($organization === null) {
            return;
        }

        activity()
            ->causedBy($session->operator()->first())
            ->performedOn($organization)
            ->withProperties(['assistance_session_id' => $session->id])
            ->event('assistance_closed')
            ->log('Accès d’assistance fermé');
    }
}
