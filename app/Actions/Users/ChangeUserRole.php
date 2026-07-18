<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Exceptions\AdministrationException;
use App\Models\User;
use App\Support\OrganizationAdmins;
use Spatie\Permission\PermissionRegistrar;

/**
 * Change le rôle unique d'un compte (RG-13). Interdit de rétrograder le dernier
 * administrateur actif (RG-11).
 */
class ChangeUserRole
{
    public function handle(User $user, UserRole $role): void
    {
        if ($role !== UserRole::Admin && app(OrganizationAdmins::class)->isLastActiveAdmin($user)) {
            throw AdministrationException::lastActiveAdmin();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        $user->syncRoles([$role->value]);
    }
}
