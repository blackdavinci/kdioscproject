<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\UserStatus;
use App\Exceptions\InvitationException;
use App\Models\Invitation;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Accepte une invitation (RG-16/17) : crée le compte et sa fiche membre dans la même
 * transaction, ou rattache le compte à une fiche existante (prévention des doublons).
 * Assigne le rôle dans le contexte de l'organisation et marque l'invitation acceptée.
 */
class AcceptInvitation
{
    public function handle(
        Invitation $invitation,
        string $password,
        ?string $fullName = null,
    ): User {
        if (! $invitation->isPending()) {
            throw InvitationException::notAcceptable();
        }

        // Fiche pré-choisie par l'admin à l'invitation (RG-16), le cas échéant.
        $linkTo = $invitation->teamMember;

        if ($linkTo !== null && $linkTo->user_id !== null) {
            throw InvitationException::teamMemberAlreadyLinked();
        }

        return DB::transaction(function () use ($invitation, $password, $linkTo, $fullName): User {
            app(TenantContext::class)->set($invitation->organization_id);
            setPermissionsTeamId($invitation->organization_id);

            $teamMember = $linkTo ?? TeamMember::create([
                'organization_id' => $invitation->organization_id,
                'full_name' => $fullName ?? $invitation->email,
            ]);

            $user = new User([
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'locale' => 'fr',
                'status' => UserStatus::Active,
                'expires_at' => $invitation->account_expires_at,
            ]);
            $user->organization_id = $invitation->organization_id;
            $user->team_member_id = $teamMember->getKey();
            $user->save();

            // Lien réciproque fiche ↔ compte (RG-16).
            $teamMember->forceFill(['user_id' => $user->getKey()])->save();

            $user->assignRole($invitation->role->value);

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });
    }
}
