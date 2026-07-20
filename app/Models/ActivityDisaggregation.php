<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisaggregationDimension;
use App\Enums\DisaggregationPhase;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Décompte désagrégé de participants d'une activité (RGA-05) : par phase
 * (prévu / réel) et par axe (sexe / âge).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $activity_id
 * @property DisaggregationPhase $phase
 * @property DisaggregationDimension $dimension
 * @property string $key
 * @property int $count
 */
class ActivityDisaggregation extends Model
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
            'phase' => DisaggregationPhase::class,
            'dimension' => DisaggregationDimension::class,
            'count' => 'integer',
        ];
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
