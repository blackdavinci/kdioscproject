<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * À appliquer sur tout modèle appartenant à une organisation (RG-01, RG-02).
 *
 * - installe le global scope d'isolation ({@see OrganizationScope}) ;
 * - renseigne automatiquement `organization_id` à la création depuis le contexte
 *   de tenant courant, afin qu'aucune donnée ne puisse être créée « hors tenant »
 *   par omission.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('organization_id'))) {
                $organizationId = app(TenantContext::class)->id();

                if ($organizationId !== null) {
                    $model->setAttribute('organization_id', $organizationId);
                }
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
