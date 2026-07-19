<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\InvitationException;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\InvitationBlocked;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Émet une invitation (RG-07/17). Conformément au modèle §3/§4, le compte et sa fiche
 * membre sont créés dès l'invitation en statut `invited` (le compte s'activera à la
 * définition du mot de passe) ; une fiche existante peut être rattachée à la place (RG-16).
 *
 * Anti-énumération : si l'adresse est déjà titulaire d'un compte (toute organisation),
 * rien n'est créé et l'admin émetteur reçoit une notification interne ; côté appelant,
 * le message reste générique.
 *
 * Renvoie l'invitation, ou null si l'émission a été silencieusement supprimée.
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
        ?string $fullName = null,
        ?string $phone = null,
    ): ?Invitation {
        $email = mb_strtolower(trim($email));

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $sentBy?->notify(new InvitationBlocked($email));

            return null;
        }

        if ($linkTo !== null) {
            if ($linkTo->organization_id !== $organization->getKey()) {
                throw InvitationException::teamMemberFromAnotherOrganization();
            }
            if ($linkTo->user_id !== null) {
                throw InvitationException::teamMemberAlreadyLinked();
            }
        }

        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($organization, $email, $role, $sentBy, $accountExpiresAt, $linkTo, $token, $fullName, $phone): Invitation {
            app(TenantContext::class)->set($organization->getKey());
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->getKey());

            // Fiche membre : rattachement à une fiche existante (RG-16) ou création (RG-17).
            // Le nom réel est renseigné à l'acceptation s'il n'est pas fourni ici.
            $teamMember = $linkTo ?? TeamMember::create([
                'organization_id' => $organization->getKey(),
                'full_name' => ($fullName !== null && $fullName !== '') ? $fullName : $email,
                'phone' => $phone,
            ]);

            // Compte en statut `invited`, sans mot de passe (activé à l'acceptation).
            $user = new User([
                'email' => $email,
                'locale' => 'fr',
                'status' => UserStatus::Invited,
                'expires_at' => $role->isTemporary() ? $accountExpiresAt : null,
            ]);
            $user->organization_id = $organization->getKey();
            $user->team_member_id = $teamMember->getKey();
            $user->save();

            $teamMember->forceFill(['user_id' => $user->getKey()])->save();

            $user->assignRole($role->value);

            $invitation = new Invitation([
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => Carbon::now()->addHours(72),
                'account_expires_at' => $role->isTemporary() ? $accountExpiresAt : null,
                'team_member_id' => $teamMember->getKey(),
                'sent_by' => $sentBy?->getKey(),
            ]);
            $invitation->organization_id = $organization->getKey();
            $invitation->save();

            return $invitation;
        });

        (new DeliverInvitation)->handle($invitation, $token);

        return $invitation;
    }
}
