<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisaggregationDimension;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Répartition désagrégée d'une valeur d'indicateur (RGSE-04) : sexe, âge ou localité.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $indicator_value_id
 * @property DisaggregationDimension $dimension
 * @property string $key
 * @property float $count
 */
class IndicatorValueDisaggregation extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimension' => DisaggregationDimension::class,
            'count' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<IndicatorValue, $this> */
    public function indicatorValue(): BelongsTo
    {
        return $this->belongsTo(IndicatorValue::class);
    }
}
