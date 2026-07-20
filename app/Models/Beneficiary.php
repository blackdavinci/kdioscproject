<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeBracket;
use App\Enums\Sex;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\BeneficiaryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Bénéficiaire (RGSE-09..12) : identifiant unique par organisation, données
 * minimales désagrégées, nominatifs CHIFFRÉS (encrypted casts) — jamais en clair
 * dans les rapports, exports ou l'audit. `name_fingerprint` sert au rapprochement.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $code
 * @property Sex|null $sex
 * @property AgeBracket|null $age_bracket
 * @property int|null $birth_year
 * @property string|null $locality_id
 * @property string|null $geo_unit_id
 * @property string|null $full_name
 * @property string|null $contact
 * @property string|null $name_fingerprint
 */
class Beneficiary extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<BeneficiaryFactory> */
    use HasFactory;

    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'age_bracket' => AgeBracket::class,
            'birth_year' => 'integer',
            // Nominatifs chiffrés au repos (loi L/2016/037, RGSE-09).
            'full_name' => 'encrypted',
            'contact' => 'encrypted',
        ];
    }

    /**
     * Audit sans les nominatifs (RGSE-13) : on n'écrit jamais nom/contact/empreinte
     * dans le journal.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logExcept(['full_name', 'contact', 'name_fingerprint', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<Locality, $this> */
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    /** @return BelongsTo<GeoUnit, $this> */
    public function geoUnit(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class);
    }

    /** @return BelongsToMany<Activity, $this> */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'beneficiary_activity');
    }
}
