<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Localité propre à une organisation (RG-23), rattachée à une unité géo nationale.
 * Scopée par organization_id : un sélecteur de lieu montre le référentiel national
 * commun plus les seules localités de l'organisation courante.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $geo_unit_id
 * @property string $name
 */
class Locality extends TenantModel
{
    protected $guarded = ['id'];

    /** @return BelongsTo<GeoUnit, $this> */
    public function geoUnit(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class);
    }
}
