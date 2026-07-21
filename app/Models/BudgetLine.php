<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseKind;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\BudgetLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Ligne budgétaire d'un projet (RGB-02). Les agrégats (engagé/dépensé/disponible)
 * sont calculés, jamais stockés (RGB-06).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string|null $budget_category_id
 * @property string $label
 * @property int $amount_gnf
 * @property int $threshold_percent
 * @property Carbon|null $alert_notified_at
 */
class BudgetLine extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<BudgetLineFactory> */
    use HasFactory;

    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'threshold_percent' => 80,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gnf' => 'integer',
            'threshold_percent' => 'integer',
            'alert_notified_at' => 'datetime',
        ];
    }

    public function spent(): int
    {
        return (int) $this->expenses()->where('kind', ExpenseKind::Realisee->value)->sum('amount_gnf');
    }

    public function committed(): int
    {
        return (int) $this->expenses()->where('kind', ExpenseKind::Engagement->value)->sum('amount_gnf');
    }

    public function available(): int
    {
        return $this->amount_gnf - $this->spent() - $this->committed();
    }

    public function consumptionRate(): ?float
    {
        return $this->amount_gnf > 0 ? $this->spent() / $this->amount_gnf : null;
    }

    public function isOverThreshold(): bool
    {
        $rate = $this->consumptionRate();

        return $rate !== null && $rate * 100 >= $this->threshold_percent;
    }

    public function isOverspent(): bool
    {
        return $this->spent() > $this->amount_gnf;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<BudgetCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    /** @return HasMany<BudgetLineAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetLineAllocation::class);
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
