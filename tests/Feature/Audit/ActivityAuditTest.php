<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Spatie\Permission\PermissionRegistrar;

it('journalise la création/modification d’une entité et la rattache à son organisation (RG-26)', function (): void {
    $org = Organization::factory()->create();

    app(TenantContext::class)->set($org->id);
    $member = TeamMember::factory()->create(['organization_id' => $org->id, 'full_name' => 'Aïcha']);

    $created = ActivityLog::query()
        ->where('subject_type', $member->getMorphClass())
        ->where('subject_id', $member->id)
        ->where('event', 'created')
        ->first();

    expect($created)->not->toBeNull()
        ->and($created->organization_id)->toBe($org->id);

    $member->update(['full_name' => 'Aïcha Baldé']);

    expect(ActivityLog::query()->where('subject_id', $member->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('enregistre l’auteur (causer) de l’action depuis le guard authentifié (RG-26)', function (): void {
    $org = Organization::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $admin = User::factory()->create(['organization_id' => $org->id]);
    $this->actingAs($admin); // guard web

    app(TenantContext::class)->set($org->id);
    $member = TeamMember::factory()->create(['organization_id' => $org->id]);

    $activity = ActivityLog::query()->where('subject_id', $member->id)->where('event', 'created')->first();

    expect($activity?->causer_id)->toBe($admin->id);
});
