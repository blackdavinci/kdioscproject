<?php

declare(strict_types=1);

use App\Actions\Assistance\EndAssistanceAccess;
use App\Actions\Assistance\StartAssistanceAccess;
use App\Models\ActivityLog;
use App\Models\AssistanceSession;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create();
    $this->operator = PlatformUser::factory()->create(['name' => 'KIDIANI Support']);
});

it('ouvre un accès d’assistance de 24 h, tracé avec l’opérateur comme auteur (RG-14)', function (): void {
    $session = (new StartAssistanceAccess)->handle($this->org, $this->operator);

    expect($session->isActive())->toBeTrue()
        ->and($session->expires_at->between(now()->addHours(23), now()->addHours(25)))->toBeTrue();

    $activity = ActivityLog::query()->where('event', 'assistance_opened')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->organization_id)->toBe($this->org->id)
        ->and($activity->causer_type)->toBe($this->operator->getMorphClass())
        ->and($activity->causer_id)->toBe($this->operator->id);
});

it('clôt l’accès d’assistance et le trace (RG-14)', function (): void {
    $session = (new StartAssistanceAccess)->handle($this->org, $this->operator);

    (new EndAssistanceAccess)->handle($session);

    expect($session->fresh()->isActive())->toBeFalse()
        ->and(AssistanceSession::activeFor($this->org->id))->toBeNull()
        ->and(ActivityLog::query()->where('event', 'assistance_closed')->exists())->toBeTrue();
});

it('ignore une session expirée (RG-14)', function (): void {
    $session = (new StartAssistanceAccess)->handle($this->org, $this->operator);
    $session->forceFill(['expires_at' => now()->subHour()])->save();

    expect(AssistanceSession::activeFor($this->org->id))->toBeNull();
});

it('affiche le bandeau d’assistance aux utilisateurs de l’organisation (RG-14)', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $agent = User::factory()->create(['organization_id' => $this->org->id]);
    $agent->assignRole('agent_terrain');

    (new StartAssistanceAccess)->handle($this->org, $this->operator);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($agent);
    app(TenantContext::class)->set($this->org->id);

    $this->get('/app/'.$this->org->slug)
        ->assertSee('accès d’assistance technique', false)
        ->assertSee('KIDIANI Support', false);
});
