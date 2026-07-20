<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cible périodisée d'un indicateur (RGSE-02).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $indicator_id
 * @property string $period_label
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property float $target_value
 */
class IndicatorTarget extends Model
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
            'period_start' => 'date',
            'period_end' => 'date',
            'target_value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Indicator, $this> */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
