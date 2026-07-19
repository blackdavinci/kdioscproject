<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
});

it('redirige un admin sans 2FA vers Mon profil pour la configurer (RG-09)', function (): void {
    $admin = User::factory()->create(['organization_id' => $this->org->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    expect($admin->hasConfirmedTwoFactor())->toBeFalse();

    $this->get('/app/'.$this->org->slug)
        ->assertRedirectContains('my-profile');
});

it('n’impose pas la 2FA aux rôles non-admin (RG-09 : proposée seulement)', function (): void {
    $agent = User::factory()->create(['organization_id' => $this->org->id]);
    $agent->assignRole('agent_terrain');

    $this->actingAs($agent);

    $this->get('/app/'.$this->org->slug)->assertSuccessful();
});
