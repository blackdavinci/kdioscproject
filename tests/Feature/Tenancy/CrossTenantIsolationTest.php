<?php

declare(strict_types=1);

use App\Models\Donor;
use App\Models\GeoUnit;
use App\Models\Invitation;
use App\Models\Locality;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/*
|--------------------------------------------------------------------------
| Suite d'isolation inter-organisations sur les entités réelles (critère 1)
|--------------------------------------------------------------------------
|
| Deux organisations A et B sont peuplées ; on vérifie qu'aucun accès croisé n'est
| possible depuis le contexte d'une organisation : accès direct par ULID → introuvable,
| comptages et recherches scopés, et ce pour chaque entité tenant du socle (RG-02).
|
*/

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);

    $this->geoUnit = GeoUnit::create(['pcode' => 'GN-TEST', 'level' => 3, 'name' => 'Unité de test']);

    $this->orgA = Organization::factory()->create(['name' => 'ONG Alpha']);
    $this->orgB = Organization::factory()->create(['name' => 'ONG Beta']);

    $this->dataFor = function (Organization $org, string $suffix): array {
        return app(TenantContext::class)->runFor($org->id, fn (): array => [
            'donor' => Donor::create(['name' => "Bailleur {$suffix}", 'type' => 'multilateral']),
            'locality' => Locality::create(['geo_unit_id' => $this->geoUnit->id, 'name' => "Localité {$suffix}"]),
            'teamMember' => TeamMember::create(['full_name' => "Membre {$suffix}"]),
            'tag' => Tag::create(['name' => "Étiquette {$suffix}"]),
            'invitation' => Invitation::create([
                'organization_id' => $org->id,
                'email' => "invite-{$suffix}@example.com",
                'role' => 'chef_projet',
                'token_hash' => hash('sha256', "tok-{$suffix}"),
                'expires_at' => now()->addHours(72),
            ]),
        ]);
    };

    $this->a = ($this->dataFor)($this->orgA, 'A');
    $this->b = ($this->dataFor)($this->orgB, 'B');
});

it('ne compte que les données de l’organisation courante, pour chaque entité (RG-02)', function (): void {
    app(TenantContext::class)->set($this->orgA->id);

    expect(Donor::count())->toBe(1)
        ->and(Locality::count())->toBe(1)
        ->and(TeamMember::count())->toBe(1)
        ->and(Tag::count())->toBe(1)
        ->and(Invitation::count())->toBe(1)
        ->and(Donor::sole()->name)->toBe('Bailleur A')
        ->and(Tag::sole()->name)->toBe('Étiquette A');
});

it('rend introuvable toute entité d’une autre organisation par son ULID direct → 404 (RG-02, RG-03)', function (): void {
    app(TenantContext::class)->set($this->orgA->id);

    expect(Donor::find($this->b['donor']->id))->toBeNull()
        ->and(Locality::find($this->b['locality']->id))->toBeNull()
        ->and(TeamMember::find($this->b['teamMember']->id))->toBeNull()
        ->and(Tag::find($this->b['tag']->id))->toBeNull()
        ->and(Invitation::find($this->b['invitation']->id))->toBeNull();

    expect(fn () => Donor::query()->findOrFail($this->b['donor']->id))
        ->toThrow(ModelNotFoundException::class);
});

it('scope aussi la recherche et l’autocomplete de mentions (RG-02)', function (): void {
    app(TenantContext::class)->set($this->orgA->id);

    // Recherche par nom : ne remonte jamais un membre d'une autre organisation.
    expect(TeamMember::where('full_name', 'like', 'Membre%')->pluck('full_name')->all())
        ->toBe(['Membre A']);

    app(TenantContext::class)->set($this->orgB->id);

    expect(TeamMember::where('full_name', 'like', 'Membre%')->pluck('full_name')->all())
        ->toBe(['Membre B']);
});

it('scope les comptes utilisateurs par organisation (RG-02, RG-06)', function (): void {
    $userA = User::factory()->create(['organization_id' => $this->orgA->id]);
    $userB = User::factory()->create(['organization_id' => $this->orgB->id]);

    app(TenantContext::class)->set($this->orgA->id);

    expect(User::find($userB->id))->toBeNull()
        ->and(User::find($userA->id))->not->toBeNull()
        ->and(User::pluck('id')->all())->toBe([$userA->id]);
});

it('expose le référentiel géo national commun aux deux organisations (RG-23)', function (): void {
    // geo_units est hors tenant : visible quel que soit le contexte.
    app(TenantContext::class)->set($this->orgA->id);
    expect(GeoUnit::find($this->geoUnit->id))->not->toBeNull();

    app(TenantContext::class)->set($this->orgB->id);
    expect(GeoUnit::find($this->geoUnit->id))->not->toBeNull();
});
