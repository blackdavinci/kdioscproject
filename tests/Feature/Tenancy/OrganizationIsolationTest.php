<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToOrganization;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Preuve du mécanisme d'isolation multi-tenant (RG-02)
|--------------------------------------------------------------------------
|
| Ce test ne dépend d'aucune entité de la Spec 01 : il exerce le trait
| BelongsToOrganization et son global scope sur un modèle fictif adossé à une
| table temporaire, afin de prouver — dès la Phase 0 — qu'aucune donnée d'une
| autre organisation n'est atteignable (accès direct par identifiant, comptage,
| recherche). La suite d'isolation complète (URL, exports, mentions…) est
| construite en Spec 01 sur les entités réelles.
|
*/

/**
 * Modèle fictif porté par la table temporaire `fixture_records`.
 */
class FixtureRecord extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $table = 'fixture_records';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FixtureRecord $record): void {
            if (empty($record->getKey())) {
                $record->setAttribute($record->getKeyName(), (string) Str::ulid());
            }
        });
    }
}

beforeEach(function (): void {
    Schema::create('fixture_records', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('organization_id');
        $table->string('name');
    });
});

/**
 * Crée un enregistrement pour une organisation donnée, sans dépendre du scope.
 */
function createRecordFor(string $organizationId, string $name): FixtureRecord
{
    return app(TenantContext::class)->runFor($organizationId, fn () => FixtureRecord::create(['name' => $name]));
}

it('affecte automatiquement organization_id depuis le contexte courant (RG-01)', function (): void {
    $record = createRecordFor('org-A', 'Activité A');

    expect($record->getAttribute('organization_id'))->toBe('org-A')
        ->and($record->getKey())->toBeUlid();
});

it('ne renvoie que les données de l’organisation courante — comptage et recherche (RG-02)', function (): void {
    createRecordFor('org-A', 'Alpha');
    createRecordFor('org-A', 'Bravo');
    createRecordFor('org-B', 'Charlie');

    app(TenantContext::class)->set('org-A');

    expect(FixtureRecord::count())->toBe(2)
        ->and(FixtureRecord::pluck('name')->all())->toEqualCanonicalizing(['Alpha', 'Bravo'])
        ->and(FixtureRecord::where('name', 'like', '%')->get())->toHaveCount(2);
});

it('rend inatteignable un enregistrement d’une autre organisation par son ULID direct → 404 (RG-02, RG-03)', function (): void {
    $foreign = createRecordFor('org-B', 'Ressource privée de B');

    app(TenantContext::class)->set('org-A');

    // Accès direct par identifiant (équivalent d'une URL /.../{ulid} d'un autre tenant).
    expect(FixtureRecord::find($foreign->getKey()))->toBeNull();

    expect(fn () => FixtureRecord::findOrFail($foreign->getKey()))
        ->toThrow(ModelNotFoundException::class);
});

it('bascule correctement la visibilité selon l’organisation active (RG-02)', function (): void {
    $a = createRecordFor('org-A', 'Doc A');
    $b = createRecordFor('org-B', 'Doc B');

    $context = app(TenantContext::class);

    $context->set('org-A');
    expect(FixtureRecord::find($a->getKey()))->not->toBeNull()
        ->and(FixtureRecord::find($b->getKey()))->toBeNull();

    $context->set('org-B');
    expect(FixtureRecord::find($b->getKey()))->not->toBeNull()
        ->and(FixtureRecord::find($a->getKey()))->toBeNull();
});

it('sans contexte de tenant, le scope ne restreint pas (traitements multi-organisations)', function (): void {
    createRecordFor('org-A', 'X');
    createRecordFor('org-B', 'Y');

    app(TenantContext::class)->forget();

    expect(FixtureRecord::count())->toBe(2);
});
