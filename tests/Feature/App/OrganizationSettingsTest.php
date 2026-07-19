<?php

declare(strict_types=1);

use App\Filament\App\Pages\Tenancy\EditOrganizationProfile;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI', 'slug' => 'ablogui', 'currency' => 'GNF']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->org);
    app(TenantContext::class)->set($this->org->id);
});

it('réserve les paramètres de l’organisation aux administrateurs (§5-1)', function (): void {
    expect(EditOrganizationProfile::canView($this->org))->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->org->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(EditOrganizationProfile::canView($this->org))->toBeFalse();
});

it('modifie le profil, le sous-domaine et les préférences de notification (§5-1)', function (): void {
    Livewire::test(EditOrganizationProfile::class)
        ->fillForm([
            'name' => 'ABLOGUI',
            'slug' => 'ablogui-gn',
            'currency' => 'GNF',
            'fiscal_year_start' => 4,
            'settings' => ['notifications' => [
                'from_name' => 'ABLOGUI Guinée',
                'sms_enabled' => true,
                'sms_monthly_quota' => 500,
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $org = $this->org->fresh();

    expect($org->slug)->toBe('ablogui-gn')
        ->and($org->fiscal_year_start)->toBe(4)
        ->and($org->notificationSettings()->fromName)->toBe('ABLOGUI Guinée')
        ->and($org->notificationSettings()->smsEnabled)->toBeTrue()
        ->and($org->subdomainUrl())->toBe('ablogui-gn.kidiani.com');
});
