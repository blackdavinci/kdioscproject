<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope restreignant toute requête sur un modèle tenant à l'organisation
 * courante (RG-02). Actif partout où Eloquent est utilisé : requêtes web,
 * recherche, autocomplete, exports, jobs et commandes.
 *
 * Lorsqu'aucune organisation n'est établie dans le contexte, le scope ne filtre
 * pas : les traitements légitimement multi-organisations (super-admin, seeders)
 * doivent établir le contexte via {@see TenantContext} ou retirer le scope
 * explicitement (`withoutGlobalScope`).
 *
 * @implements Scope<Model>
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = app(TenantContext::class)->id();

        if ($organizationId !== null) {
            $builder->where($model->getTable().'.organization_id', $organizationId);
        }
    }
}
