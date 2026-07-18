<?php

declare(strict_types=1);

use App\Actions\Invitations\AcceptInvitation;
use App\Actions\Invitations\ResendInvitation;
use App\Actions\Invitations\SendInvitation;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\InvitationException;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\InvitationBlocked;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->organization = Organization::factory()->create();
});

it('émet une invitation avec lien 72 h et envoie l’e-mail (RG-07)', function (): void {
    Mail::fake();

    $invitation = (new SendInvitation)->handle($this->organization, 'Nouvel.Admin@example.com', UserRole::Admin);

    expect($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('nouvel.admin@example.com')
        ->and($invitation->expires_at->between(now()->addHours(71), now()->addHours(73)))->toBeTrue()
        ->and($invitation->token_hash)->not->toBeEmpty();

    Mail::assertSent(InvitationMail::class);
});

it('applique l’anti-énumération : e-mail déjà titulaire → aucune invitation, notification interne à l’admin (RG-07)', function (): void {
    Mail::fake();
    Notification::fake();

    $existing = User::factory()->create(['email' => 'deja@example.com']);
    $admin = User::factory()->create();

    $result = (new SendInvitation)->handle($this->organization, 'deja@example.com', UserRole::ChefProjet, $admin);

    expect($result)->toBeNull()
        ->and(Invitation::where('email', 'deja@example.com')->exists())->toBeFalse();

    Mail::assertNothingSent();
    Notification::assertSentTo($admin, InvitationBlocked::class);
});

it('accepte l’invitation : crée le compte et sa fiche dans la même organisation, assigne le rôle (RG-17)', function (): void {
    Mail::fake();

    $invitation = (new SendInvitation)->handle($this->organization, 'chef@example.com', UserRole::ChefProjet);

    $user = (new AcceptInvitation)->handle($invitation, 'motdepasse-solide', 'Awa Diallo');

    expect($user->organization_id)->toBe($this->organization->id)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->teamMember->full_name)->toBe('Awa Diallo')
        ->and($user->teamMember->organization_id)->toBe($this->organization->id)
        ->and($invitation->fresh()->isAccepted())->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
    expect($user->fresh()->hasRole(UserRole::ChefProjet->value))->toBeTrue();
});

it('rattache le compte à une fiche membre existante sans perte d’historique (RG-16)', function (): void {
    Mail::fake();

    app(TenantContext::class)->set($this->organization->id);
    $member = TeamMember::factory()->create(['organization_id' => $this->organization->id, 'full_name' => 'Moussa Camara']);
    app(TenantContext::class)->forget();

    $invitation = (new SendInvitation)->handle(
        $this->organization, 'moussa@example.com', UserRole::AgentTerrain, null, null, $member,
    );

    $user = (new AcceptInvitation)->handle($invitation, 'motdepasse-solide');

    expect($user->team_member_id)->toBe($member->id)
        ->and($member->fresh()->user_id)->toBe($user->id)
        ->and(TeamMember::withoutGlobalScopes()->where('organization_id', $this->organization->id)->count())->toBe(1);
});

it('bloque la liaison à une fiche déjà rattachée (RG-16, cas limite)', function (): void {
    app(TenantContext::class)->set($this->organization->id);
    $member = TeamMember::factory()->create(['organization_id' => $this->organization->id]);
    User::factory()->create(['organization_id' => $this->organization->id, 'team_member_id' => $member->id]);
    $member->forceFill(['user_id' => User::withoutGlobalScopes()->where('team_member_id', $member->id)->value('id')])->save();
    app(TenantContext::class)->forget();

    expect(fn () => (new SendInvitation)->handle(
        $this->organization, 'x@example.com', UserRole::AgentTerrain, null, null, $member,
    ))->toThrow(InvitationException::class);
});

it('refuse une invitation expirée (RG-07, critère 3)', function (): void {
    Mail::fake();

    $invitation = (new SendInvitation)->handle($this->organization, 'tard@example.com', UserRole::Consultant);
    $invitation->forceFill(['expires_at' => now()->subMinute()])->save();

    expect($invitation->isPending())->toBeFalse()
        ->and(fn () => (new AcceptInvitation)->handle($invitation, 'motdepasse-solide'))
        ->toThrow(InvitationException::class);
});

it('le lien signé valide affiche le formulaire d’activation', function (): void {
    $token = 'jeton-clair-valide';
    $invitation = Invitation::create([
        'organization_id' => $this->organization->id,
        'email' => 'lien@example.com',
        'role' => UserRole::Admin,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHours(72),
    ]);

    $url = URL::temporarySignedRoute(
        'invitation.accept', $invitation->expires_at, ['invitation' => $invitation->id, 'token' => $token],
    );

    $this->get($url)->assertOk()->assertSee('Activer mon compte');
});

it('un jeton falsifié renvoie la page « lien expiré » sans fuite', function (): void {
    $invitation = Invitation::create([
        'organization_id' => $this->organization->id,
        'email' => 'faux@example.com',
        'role' => UserRole::Admin,
        'token_hash' => hash('sha256', 'le-vrai-jeton'),
        'expires_at' => now()->addHours(72),
    ]);

    // Signature valide mais jeton erroné.
    $url = URL::temporarySignedRoute(
        'invitation.accept', $invitation->expires_at, ['invitation' => $invitation->id, 'token' => 'mauvais-jeton'],
    );

    $this->get($url)->assertOk()->assertSee(__('invitations.link_expired_title'));
});

it('renvoie l’invitation en régénérant le jeton et en repoussant l’expiration (RG-07)', function (): void {
    Mail::fake();

    $invitation = (new SendInvitation)->handle($this->organization, 'renvoi@example.com', UserRole::ResponsableSe);
    $oldHash = $invitation->token_hash;
    $invitation->forceFill(['expires_at' => now()->subHour()])->save();

    (new ResendInvitation)->handle($invitation);

    expect($invitation->token_hash)->not->toBe($oldHash)
        ->and($invitation->fresh()->isPending())->toBeTrue();

    Mail::assertSent(InvitationMail::class);
});
