<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GeoUnitFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unité administrative nationale COD-AB (RG-21) — hors tenant, lecture seule pour
 * les organisations. Arbre par parent_id (4 niveaux). Non soft-deletable : les
 * unités retirées d'une édition sont marquées inactive (RG-22).
 *
 * @property string $id
 * @property string $pcode
 * @property int $level
 * @property string|null $parent_id
 * @property string $name
 * @property bool $active
 */
class GeoUnit extends Model
{
    /** @use HasFactory<GeoUnitFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'geo_units';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'active' => 'boolean',
            'center_lat' => 'float',
            'center_lon' => 'float',
        ];
    }

    /** @return BelongsTo<GeoUnit, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class, 'parent_id');
    }

    /** @return HasMany<GeoUnit, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(GeoUnit::class, 'parent_id');
    }

    /** @return HasMany<Locality, $this> */
    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
