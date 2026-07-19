<?php

declare(strict_types=1);

use App\Actions\Organizations\CreateOrganization;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Organizations\Pages\CreateOrganization as CreateOrganizationPage;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\PlatformUser;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
});

it('crée l’organisation et invite son premier administrateur (story 1.1, RG-01)', function (): void {
    Mail::fake();

    ['organization' => $org, 'invitation' => $invitation] = (new CreateOrganization)->handle(
        ['name' => 'ABLOGUI', 'sigle' => 'ABL', 'currency' => 'GNF', 'fiscal_year_start' => 1],
        'admin@ablogui.gn',
    );

    expect($org->exists)->toBeTrue()
        ->and($org->isActive())->toBeTrue()
        ->and($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('admin@ablogui.gn')
        ->and($invitation->role)->toBe(UserRole::Admin)
        ->and($invitation->organization_id)->toBe($org->id);

    Mail::assertSent(InvitationMail::class);
});

it('le super-admin crée une organisation via l’écran et déclenche l’invitation', function (): void {
    Mail::fake();

    $superAdmin = PlatformUser::factory()->create();
    $this->actingAs($superAdmin, 'platform');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateOrganizationPage::class)
        ->fillForm([
            'name' => 'ONG Pilote',
            'sigle' => 'ONGP',
            'currency' => 'GNF',
            'fiscal_year_start' => 1,
            'admin_first_name' => 'Aïssatou',
            'admin_last_name' => 'Barry',
            'admin_phone' => '+224 620 11 22 33',
            'admin_email' => 'premier.admin@ongpilote.gn',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $org = Organization::where('name', 'ONG Pilote')->sole();

    $invitation = Invitation::where('organization_id', $org->id)->where('email', 'premier.admin@ongpilote.gn')->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->teamMember->full_name)->toBe('Aïssatou Barry')
        ->and($invitation->teamMember->phone)->toBe('+224 620 11 22 33');

    Mail::assertSent(InvitationMail::class);
});
