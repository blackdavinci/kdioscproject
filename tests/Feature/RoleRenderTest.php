<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Rendu des pages accessibles selon le rôle (chemins distincts d'un admin).
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    config(['kdiosc.enforce_admin_two_factor' => false]);
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI', 'slug' => 'ablogui']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app(TenantContext::class)->set($this->org->id);
});

function actingRole(Organization $org, string $role): User
{
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole($role);

    return $user;
}

it('affiche la vue « Projets partagés » au bailleur sans erreur (RGP-16)', function (): void {
    $bailleur = actingRole($this->org, 'bailleur');

    $this->actingAs($bailleur)->get('/app/ablogui/shared-projects')->assertSuccessful();
});

it('affiche le tableau de bord au chef de projet, agent, financier et S&E', function (string $role): void {
    $user = actingRole($this->org, $role);

    $this->actingAs($user)->get('/app/ablogui')->assertSuccessful();
})->with(['chef_projet', 'agent_terrain', 'responsable_financier', 'responsable_se']);

it('affiche le suivi budgétaire au responsable financier', function (): void {
    $user = actingRole($this->org, 'responsable_financier');

    $this->actingAs($user)->get('/app/ablogui/budget-tracking')->assertSuccessful();
});

it('affiche « Mes tâches » à l’agent de terrain', function (): void {
    $user = actingRole($this->org, 'agent_terrain');

    $this->actingAs($user)->get('/app/ablogui/my-tasks')->assertSuccessful();
});
