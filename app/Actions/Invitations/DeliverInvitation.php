<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Construit le lien d'acceptation signé (valable jusqu'à l'expiration de
 * l'invitation, 72 h) et envoie l'e-mail. Le jeton en clair n'est jamais persisté :
 * seul son hachage l'est (RG-07).
 */
class DeliverInvitation
{
    public function handle(Invitation $invitation, string $token): void
    {
        $url = URL::temporarySignedRoute(
            'invitation.accept',
            $invitation->expires_at,
            ['invitation' => $invitation->getKey(), 'token' => $token],
        );

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $url));
    }
}
