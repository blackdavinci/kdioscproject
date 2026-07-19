<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\UserResource;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);

    $this->organization = Organization::factory()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
    $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->organization);
});

it('réserve la gestion des utilisateurs aux administrateurs (matrice §6)', function (): void {
    expect(UserResource::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->organization->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(UserResource::canAccess())->toBeFalse();
});

it('l’admin invite un chef de projet depuis l’écran : compte invited + invitation créés (critère 2)', function (): void {
    Mail::fake();

    Livewire::test(ListUsers::class)
        ->callAction('invite', [
            'email' => 'chef@ong.gn',
            'role' => 'chef_projet',
        ]);

    $invited = User::withoutGlobalScopes()->where('email', 'chef@ong.gn')->sole();

    expect($invited->organization_id)->toBe($this->organization->id)
        ->and($invited->status)->toBe(UserStatus::Invited)
        ->and(Invitation::withoutGlobalScopes()->where('email', 'chef@ong.gn')->exists())->toBeTrue();

    Mail::assertSent(InvitationMail::class);
});
