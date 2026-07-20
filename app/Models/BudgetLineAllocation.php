<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Répartition d'une ligne budgétaire entre bailleurs (RGB-03, cofinancement).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $budget_line_id
 * @property string $donor_id
 * @property int $amount_gnf
 */
class BudgetLineAllocation extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount_gnf' => 'integer'];
    }

    /** @return BelongsTo<BudgetLine, $this> */
    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    /** @return BelongsTo<Donor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}
