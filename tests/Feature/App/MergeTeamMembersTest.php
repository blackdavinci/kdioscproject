<?php

declare(strict_types=1);

use App\Actions\TeamMembers\MergeTeamMembers;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->org = Organization::factory()->create();
    app(TenantContext::class)->set($this->org->id);
});

it('fusionne une fiche en doublon : réassigne les références et archive la source (RG-16)', function (): void {
    $target = TeamMember::factory()->create(['organization_id' => $this->org->id, 'full_name' => 'Awa Diallo']);
    $source = TeamMember::factory()->create(['organization_id' => $this->org->id, 'full_name' => 'A. Diallo']);

    $invitation = Invitation::create([
        'organization_id' => $this->org->id,
        'email' => 'awa@example.com',
        'role' => UserRole::AgentTerrain,
        'token_hash' => hash('sha256', 'x'),
        'expires_at' => now()->addHours(72),
        'team_member_id' => $source->id,
    ]);

    $count = (new MergeTeamMembers)->handle($source, $target);

    expect($count)->toBe(1)
        ->and($invitation->fresh()->team_member_id)->toBe($target->id)
        ->and(TeamMember::find($source->id))->toBeNull()                       // archivée (soft delete)
        ->and(TeamMember::withTrashed()->find($source->id)->trashed())->toBeTrue();
});

it('refuse de fusionner une fiche rattachée à un compte (RG-16)', function (): void {
    $target = TeamMember::factory()->create(['organization_id' => $this->org->id]);
    $linked = TeamMember::factory()->create(['organization_id' => $this->org->id]);
    User::factory()->create(['organization_id' => $this->org->id, 'team_member_id' => $linked->id]);
    $linked->forceFill(['user_id' => User::withoutGlobalScopes()->where('team_member_id', $linked->id)->value('id')])->save();

    expect(fn () => (new MergeTeamMembers)->handle($linked, $target))
        ->toThrow(RuntimeException::class);
});
