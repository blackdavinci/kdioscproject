<?php

declare(strict_types=1);

use App\Filament\App\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\App\Resources\TeamMembers\TeamMemberResource;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->organization = Organization::factory()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
    $this->chef = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->chef->assignRole('chef_projet');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->chef);
    Filament::setTenant($this->organization);

    // En requête réelle, ApplyTenantState établit le contexte d'isolation.
    app(TenantContext::class)->set($this->organization->id);
});

it('autorise admin et chef de projet, refuse les autres rôles (matrice §6)', function (): void {
    expect(TeamMemberResource::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->organization->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(TeamMemberResource::canAccess())->toBeFalse();
});

it('un chef de projet crée un membre sans compte, rattaché à son organisation (RG-15)', function (): void {
    Livewire::test(CreateTeamMember::class)
        ->fillForm([
            'full_name' => 'Fatoumata Bah',
            'function' => 'Animatrice communautaire',
            'phone' => '+224 620 00 00 00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = TeamMember::withoutGlobalScopes()->where('full_name', 'Fatoumata Bah')->sole();

    expect($member->organization_id)->toBe($this->organization->id)
        ->and($member->user_id)->toBeNull();
});
