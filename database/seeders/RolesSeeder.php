<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Les 7 rôles fixes du socle (RG-12/13), créés globalement (team_id nul) en mode
 * teams : un même jeu de rôles, assigné par organisation via model_has_roles.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Rôles globaux : aucune équipe courante au moment de la création.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
