<?php

declare(strict_types=1);

use App\Filament\App\Pages\Billing;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->organization = Organization::factory()->create();
    Subscription::factory()->create([
        'organization_id' => $this->organization->id,
        'plan_id' => Plan::factory()->create()->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
    $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->organization);
    app(TenantContext::class)->set($this->organization->id);
});

it('affiche l’écran Abonnement & Facturation à l’admin de l’OSC (RGF-01)', function (): void {
    Livewire::test(Billing::class)
        ->assertOk()
        ->assertSee('Mon abonnement');
});

it('réserve l’écran de facturation aux administrateurs de l’organisation (matrice §6)', function (): void {
    expect(Billing::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->organization->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(Billing::canAccess())->toBeFalse();
});
