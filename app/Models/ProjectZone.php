<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unité de la zone d'intervention d'un projet (RGP-02) : soit une unité du
 * référentiel national, soit une localité d'organisation (exactement une des deux).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string|null $geo_unit_id
 * @property string|null $locality_id
 */
class ProjectZone extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $guarded = ['id'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<GeoUnit, $this> */
    public function geoUnit(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class);
    }

    /** @return BelongsTo<Locality, $this> */
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }
}
