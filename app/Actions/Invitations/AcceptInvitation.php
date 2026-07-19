<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\UserStatus;
use App\Exceptions\InvitationException;
use App\Models\Invitation;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Accepte une invitation (RG-07) : active le compte créé à l'invitation (mot de passe
 * défini, statut passé à `active`), renseigne le vrai nom sur la fiche membre et marque
 * l'invitation acceptée.
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

        return DB::transaction(function () use ($invitation, $password, $fullName): User {
            app(TenantContext::class)->set($invitation->organization_id);

            $user = User::withoutGlobalScopes()
                ->where('organization_id', $invitation->organization_id)
                ->where('email', $invitation->email)
                ->firstOrFail();

            $user->forceFill([
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
                'expires_at' => $invitation->account_expires_at,
            ])->save();

            // Renseigne le vrai nom si l'invité l'a fourni (la fiche portait l'e-mail).
            if ($fullName !== null && $fullName !== '') {
                $user->teamMember?->forceFill(['full_name' => $fullName])->save();
            }

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });
    }
}
