<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseKind;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Dépense ou engagement rattaché à une ligne budgétaire et, en option, à une
 * activité (RGB-04/05). Justificatif interne (medialibrary).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $budget_line_id
 * @property string|null $activity_id
 * @property ExpenseKind $kind
 * @property string $label
 * @property int $amount_gnf
 * @property Carbon $spent_on
 */
class Expense extends Model implements HasMedia
{
    use BelongsToOrganization;

    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use HasUlids;
    use InteractsWithMedia;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'kind' => 'realisee',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ExpenseKind::class,
            'amount_gnf' => 'integer',
            'spent_on' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('justificatif')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<BudgetLine, $this> */
    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
