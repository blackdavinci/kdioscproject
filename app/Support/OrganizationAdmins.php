<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Décompte des administrateurs actifs d'une organisation, utilisé pour garantir
 * l'invariant « au moins un administrateur actif » (RG-11).
 *
 * Interroge directement le pivot model_has_roles (scopé par organization_id) pour
 * éviter les subtilités du mode teams sur les relations Eloquent.
 */
class OrganizationAdmins
{
    public function countActive(string $organizationId): int
    {
        $adminRoleId = Role::query()
            ->where('name', UserRole::Admin->value)
            ->where('guard_name', 'web')
            ->value('id');

        if ($adminRoleId === null) {
            return 0;
        }

        return DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.role_id', $adminRoleId)
            ->where('model_has_roles.organization_id', $organizationId)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('users.status', UserStatus::Active->value)
            ->whereNull('users.deleted_at')
            ->count();
    }

    public function isLastActiveAdmin(User $user): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        return $user->status === UserStatus::Active
            && $user->hasRole(UserRole::Admin->value)
            && $this->countActive($user->organization_id) <= 1;
    }
}
