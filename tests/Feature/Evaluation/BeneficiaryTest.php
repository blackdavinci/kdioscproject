<?php

declare(strict_types=1);

use App\Filament\App\Resources\Beneficiaries\BeneficiaryResource;
use App\Filament\App\Resources\Beneficiaries\Pages\ListBeneficiaries;
use App\Models\Beneficiary;
use App\Models\Organization;
use App\Models\User;
use App\Support\BeneficiaryFingerprint;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function bootBen(Organization $org, string $role): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole($role);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    test()->actingAs($user);
    Filament::setTenant($org);
    app(TenantContext::class)->set($org->id);
}

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create(['name' => 'ABLOGUI']);
});

it('produit la même empreinte pour un même nom dans une organisation, différente ailleurs (RGSE-10)', function (): void {
    $a1 = BeneficiaryFingerprint::make('org-a', 'Aïssatou  DIALLO');
    $a2 = BeneficiaryFingerprint::make('org-a', 'aissatou diallo');
    $b = BeneficiaryFingerprint::make('org-b', 'Aïssatou Diallo');

    expect($a1)->toBe($a2)          // normalisation (casse, accents, espaces)
        ->and($a1)->not->toBe($b)   // salage par organisation
        ->and(BeneficiaryFingerprint::make('org-a', null))->toBeNull();
});

it('n’affiche jamais les nominatifs dans la liste (RGSE-09)', function (): void {
    bootBen($this->org, 'responsable_se');
    $ben = Beneficiary::factory()->create([
        'organization_id' => $this->org->id,
        'code' => 'BEN-777',
        'full_name' => 'Mariama Camara',
        'contact' => '+224610000000',
    ]);

    Livewire::test(ListBeneficiaries::class)
        ->assertCanSeeTableRecords([$ben])
        ->assertSee('BEN-777')
        ->assertDontSee('Mariama Camara')
        ->assertDontSee('+224610000000');
});

it('réserve le registre à la S&E et à l’admin (RGSE-09)', function (): void {
    bootBen($this->org, 'responsable_se');
    expect(BeneficiaryResource::canAccess())->toBeTrue();

    bootBen($this->org, 'chef_projet');
    expect(BeneficiaryResource::canAccess())->toBeFalse();

    bootBen($this->org, 'bailleur');
    expect(BeneficiaryResource::canAccess())->toBeFalse();
});
