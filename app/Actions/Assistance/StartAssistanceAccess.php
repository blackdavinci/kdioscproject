<?php

declare(strict_types=1);

namespace App\Actions\Assistance;

use App\Models\AssistanceSession;
use App\Models\Organization;
use App\Models\PlatformUser;

/**
 * Ouvre un accès d'assistance à une organisation (RG-14) : limité à 24 h, tracé à
 * l'ouverture avec l'opérateur comme auteur (identité distincte des membres). Réémet
 * la session active existante si elle est encore valide.
 */
class StartAssistanceAccess
{
    public function handle(Organization $organization, PlatformUser $operator): AssistanceSession
    {
        $existing = AssistanceSession::activeFor($organization->getKey());

        if ($existing instanceof AssistanceSession) {
            return $existing;
        }

        $session = AssistanceSession::create([
            'organization_id' => $organization->getKey(),
            'platform_user_id' => $operator->getKey(),
            'started_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        activity()
            ->causedBy($operator)
            ->performedOn($organization)
            ->withProperties(['assistance_session_id' => $session->id])
            ->event('assistance_opened')
            ->log('Accès d’assistance ouvert');

        return $session;
    }
}
