<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IndicatorValueSource;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Valeur réalisée d'un indicateur pour une période (RGSE-04), avec moyen de
 * vérification (medialibrary « verification ») et/ou référence Kobo.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $indicator_id
 * @property string $period_label
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property float $value
 * @property IndicatorValueSource $source
 * @property string|null $kobo_reference
 */
class IndicatorValue extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasUlids;
    use InteractsWithMedia;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'value' => 'decimal:2',
            'source' => IndicatorValueSource::class,
            'recorded_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('verification')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    /** @return BelongsTo<Indicator, $this> */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    /** @return HasMany<IndicatorValueDisaggregation, $this> */
    public function disaggregations(): HasMany
    {
        return $this->hasMany(IndicatorValueDisaggregation::class);
    }
}
