<?php

declare(strict_types=1);

use App\Filament\App\Pages\Portfolio;
use App\Filament\App\Pages\SharedProjects;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function actAs(Organization $org, string $role): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole($role);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($user);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);

    return $user;
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('réserve le portefeuille à la direction et au S&E (story 2.6)', function (): void {
    actAs($this->org, 'admin');
    expect(Portfolio::canAccess())->toBeTrue();

    actAs($this->org, 'chef_projet');
    expect(Portfolio::canAccess())->toBeFalse();
});

it('montre au portefeuille tous les projets de l’organisation', function (): void {
    actAs($this->org, 'responsable_se');
    $p1 = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'A']);
    $p2 = Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'B']);

    Livewire::test(Portfolio::class)->assertCanSeeTableRecords([$p1, $p2]);
});

it('ne montre au bailleur que les projets partagés et actifs (RGP-16)', function (): void {
    // Projets créés dans le contexte de l'organisation.
    $shared = app(TenantContext::class)->runFor($this->org->id, fn () => Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'SHARED']));
    $unshared = app(TenantContext::class)->runFor($this->org->id, fn () => Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'PRIVE']));

    $bailleur = actAs($this->org, 'bailleur');
    ProjectShare::create(['project_id' => $shared->id, 'user_id' => $bailleur->id, 'shared_at' => now()]);

    Livewire::test(SharedProjects::class)
        ->assertCanSeeTableRecords([$shared])
        ->assertCanNotSeeTableRecords([$unshared]);
});

it('coupe l’accès bailleur dès la révocation du partage (RGP-15)', function (): void {
    $project = app(TenantContext::class)->runFor($this->org->id, fn () => Project::factory()->create(['organization_id' => $this->org->id, 'code' => 'REVOKE']));

    $bailleur = actAs($this->org, 'bailleur');
    ProjectShare::create([
        'project_id' => $project->id,
        'user_id' => $bailleur->id,
        'shared_at' => now(),
        'revoked_at' => now(),
    ]);

    Livewire::test(SharedProjects::class)->assertCanNotSeeTableRecords([$project]);
});
