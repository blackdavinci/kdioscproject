<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsTenantActivity;
use App\Models\Concerns\NationalOrOrganizationScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rubrique budgétaire (RGB-01). organization_id NULL = rubrique nationale par
 * défaut ; sinon propre à l'organisation. Même modèle que les secteurs.
 *
 * @property string $id
 * @property string|null $organization_id
 * @property string $name
 */
class BudgetCategory extends Model
{
    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new NationalOrOrganizationScope);

        static::creating(function (BudgetCategory $category): void {
            if (! $category->getAttribute('organization_id')) {
                $category->setAttribute('organization_id', app(TenantContext::class)->id());
            }
        });
    }

    public function isNational(): bool
    {
        return $this->organization_id === null;
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
