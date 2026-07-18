<?php

declare(strict_types=1);

use App\Actions\Organizations\SetOrganizationStatus;
use App\Actions\Users\ChangeUserRole;
use App\Actions\Users\DisableUser;
use App\Enums\OrganizationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\AdministrationException;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->organization = Organization::factory()->create();
});

/**
 * Crée un compte de l'organisation courante avec un rôle donné.
 */
function makeUser(Organization $organization, UserRole $role, UserStatus $status = UserStatus::Active): User
{
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'status' => $status,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
    $user->assignRole($role->value);

    return $user;
}

it('interdit de désactiver le dernier administrateur actif (RG-11, critère 5)', function (): void {
    $admin = makeUser($this->organization, UserRole::Admin);

    expect(fn () => (new DisableUser)->handle($admin))
        ->toThrow(AdministrationException::class);

    expect($admin->fresh()->status)->toBe(UserStatus::Active);
});

it('interdit de rétrograder le dernier administrateur actif (RG-11)', function (): void {
    $admin = makeUser($this->organization, UserRole::Admin);

    expect(fn () => (new ChangeUserRole)->handle($admin, UserRole::ChefProjet))
        ->toThrow(AdministrationException::class);
});

it('autorise la désactivation d’un admin dès qu’un autre admin actif existe (RG-11)', function (): void {
    $first = makeUser($this->organization, UserRole::Admin);
    makeUser($this->organization, UserRole::Admin);

    (new DisableUser)->handle($first);

    expect($first->fresh()->status)->toBe(UserStatus::Disabled);
});

it('suspend puis réactive une organisation avec motif (RG-04, critère 5)', function (): void {
    (new SetOrganizationStatus)->suspend($this->organization, 'Non-paiement de la cotisation');

    expect($this->organization->fresh()->status)->toBe(OrganizationStatus::Suspended)
        ->and($this->organization->fresh()->suspension_reason)->toBe('Non-paiement de la cotisation');

    (new SetOrganizationStatus)->reactivate($this->organization);

    expect($this->organization->fresh()->status)->toBe(OrganizationStatus::Active)
        ->and($this->organization->fresh()->suspension_reason)->toBeNull();
});
