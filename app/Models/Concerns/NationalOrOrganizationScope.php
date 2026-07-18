<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Variante du scope d'isolation pour les référentiels « nationaux + propres »
 * (secteurs, RG-19) : une ligne est visible si elle est nationale
 * (organization_id NULL) ou appartient à l'organisation courante.
 *
 * @implements Scope<Model>
 */
class NationalOrOrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = app(TenantContext::class)->id();

        if ($organizationId === null) {
            return;
        }

        $column = $model->getTable().'.organization_id';

        $builder->where(function (Builder $query) use ($column, $organizationId): void {
            $query->whereNull($column)->orWhere($column, $organizationId);
        });
    }
}
