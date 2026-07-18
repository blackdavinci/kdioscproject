<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Renvoie une invitation (RG-07) : régénère le jeton (invalide l'ancien lien),
 * repousse l'expiration à 72 h et ré-émet l'e-mail.
 */
class ResendInvitation
{
    public function handle(Invitation $invitation): Invitation
    {
        $token = Str::random(48);

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => Carbon::now()->addHours(72),
            'accepted_at' => null,
        ])->save();

        (new DeliverInvitation)->handle($invitation, $token);

        return $invitation;
    }
}
