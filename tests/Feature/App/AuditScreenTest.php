<?php

declare(strict_types=1);

use App\Filament\App\Resources\AuditLogs\AuditLogResource;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->orgA = Organization::factory()->create();
    $this->orgB = Organization::factory()->create();

    app(TenantContext::class)->runFor($this->orgA->id, fn () => TeamMember::factory()->create(['organization_id' => $this->orgA->id]));
    app(TenantContext::class)->runFor($this->orgB->id, fn () => TeamMember::factory()->create(['organization_id' => $this->orgB->id]));
    app(TenantContext::class)->forget();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    $this->admin = User::factory()->create(['organization_id' => $this->orgA->id]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);
    Filament::setTenant($this->orgA);
});

it('n’expose au journal d’audit que les activités de l’organisation courante (RG-26)', function (): void {
    $records = AuditLogResource::getEloquentQuery()->get();

    expect($records)->not->toBeEmpty()
        ->and($records->pluck('organization_id')->unique()->all())->toBe([$this->orgA->id]);
});

it('réserve le journal d’audit aux administrateurs (matrice §6)', function (): void {
    expect(AuditLogResource::canAccess())->toBeTrue();

    $agent = User::factory()->create(['organization_id' => $this->orgA->id]);
    $agent->assignRole('agent_terrain');
    $this->actingAs($agent);

    expect(AuditLogResource::canAccess())->toBeFalse();
});
