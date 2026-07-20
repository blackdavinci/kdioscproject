<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IndicatorDirection;
use App\Enums\PeriodType;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\IndicatorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Indicateur de suivi-évaluation (RGSE-01) rattaché à un nœud du cadre logique.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $logframe_node_id
 * @property string|null $code
 * @property string $label
 * @property string|null $unit
 * @property IndicatorDirection $direction
 * @property float|null $baseline_value
 * @property PeriodType $period_type
 * @property array<string, bool>|null $disaggregations
 */
class Indicator extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<IndicatorFactory> */
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
            'direction' => IndicatorDirection::class,
            'period_type' => PeriodType::class,
            'baseline_value' => 'decimal:2',
            'baseline_date' => 'date',
            'disaggregations' => 'array',
        ];
    }

    public function hasAxis(string $axis): bool
    {
        return (bool) ($this->disaggregations[$axis] ?? false);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<LogframeNode, $this> */
    public function logframeNode(): BelongsTo
    {
        return $this->belongsTo(LogframeNode::class);
    }

    /** @return HasMany<IndicatorTarget, $this> */
    public function targets(): HasMany
    {
        return $this->hasMany(IndicatorTarget::class);
    }

    /** @return HasMany<IndicatorValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(IndicatorValue::class);
    }

    /** @return BelongsToMany<ResultFramework, $this> */
    public function frameworks(): BelongsToMany
    {
        return $this->belongsToMany(ResultFramework::class, 'result_framework_indicator');
    }
}
