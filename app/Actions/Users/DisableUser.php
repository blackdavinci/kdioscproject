<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Exceptions\AdministrationException;
use App\Models\User;
use App\Support\OrganizationAdmins;

/**
 * Désactive un compte (RG-11) : révoque ses sessions et conserve tout l'historique.
 * Interdit de désactiver le dernier administrateur actif de l'organisation.
 */
class DisableUser
{
    public function handle(User $user): void
    {
        if (app(OrganizationAdmins::class)->isLastActiveAdmin($user)) {
            throw AdministrationException::lastActiveAdmin();
        }

        $user->forceFill(['status' => UserStatus::Disabled])->save();

        RevokeUserSessions::for($user);
    }
}
