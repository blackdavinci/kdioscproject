<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\NationalOrOrganizationScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Secteur d'intervention (RG-19). organization_id NULL = secteur national par défaut
 * (visible par toutes les organisations) ; sinon secteur propre à l'organisation.
 *
 * @property string $id
 * @property string|null $organization_id
 * @property string $name
 */
class Sector extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new NationalOrOrganizationScope);

        // Un secteur créé dans un contexte tenant est rattaché à l'organisation ;
        // les secteurs nationaux sont semés hors contexte (organization_id NULL).
        static::creating(function (Sector $sector): void {
            if (! $sector->getAttribute('organization_id')) {
                $sector->setAttribute('organization_id', app(TenantContext::class)->id());
            }
        });
    }

    public function isNational(): bool
    {
        return $this->organization_id === null;
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
