<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\UserRole;
use App\Exceptions\InvitationException;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\InvitationBlocked;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Émet une invitation (RG-07). Comportement anti-énumération : si l'adresse est déjà
 * titulaire d'un compte (quelle que soit l'organisation), aucune invitation n'est
 * créée et l'admin émetteur reçoit une notification interne de l'échec réel ; côté
 * appelant, le message reste générique (cf. lang/fr/invitations.php).
 *
 * Renvoie l'invitation créée, ou null si l'émission a été silencieusement supprimée.
 */
class SendInvitation
{
    public function handle(
        Organization $organization,
        string $email,
        UserRole $role,
        ?User $sentBy = null,
        ?Carbon $accountExpiresAt = null,
        ?TeamMember $linkTo = null,
    ): ?Invitation {
        $email = mb_strtolower(trim($email));

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $sentBy?->notify(new InvitationBlocked($email));

            return null;
        }

        // Prévention des doublons (RG-16) : la fiche à rattacher est choisie ici.
        if ($linkTo !== null) {
            if ($linkTo->organization_id !== $organization->getKey()) {
                throw InvitationException::teamMemberFromAnotherOrganization();
            }
            if ($linkTo->user_id !== null) {
                throw InvitationException::teamMemberAlreadyLinked();
            }
        }

        $token = Str::random(48);

        $invitation = new Invitation([
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'expires_at' => Carbon::now()->addHours(72),
            'account_expires_at' => $role->isTemporary() ? $accountExpiresAt : null,
            'team_member_id' => $linkTo?->getKey(),
            'sent_by' => $sentBy?->getKey(),
        ]);
        $invitation->organization_id = $organization->getKey();
        $invitation->save();

        (new DeliverInvitation)->handle($invitation, $token);

        return $invitation;
    }
}
