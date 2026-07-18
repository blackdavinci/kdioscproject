<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Concerns\OrganizationScope;

/**
 * Source de vérité de l'organisation « courante » pour l'isolation multi-tenant.
 *
 * Le global scope {@see OrganizationScope} lit l'identifiant
 * ici. En requête HTTP du panel tenant, un middleware l'alimente depuis
 * l'organisation de l'utilisateur authentifié ; dans les jobs, commandes et
 * exports, l'appelant doit l'établir explicitement (RG-02).
 */
class TenantContext
{
    protected ?string $organizationId = null;

    public function id(): ?string
    {
        return $this->organizationId;
    }

    public function has(): bool
    {
        return $this->organizationId !== null;
    }

    public function set(?string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function forget(): void
    {
        $this->organizationId = null;
    }

    /**
     * Exécute un traitement sous une organisation donnée puis restaure l'état
     * précédent — utile pour les seeders, jobs et commandes multi-organisations.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runFor(?string $organizationId, callable $callback): mixed
    {
        $previous = $this->organizationId;
        $this->organizationId = $organizationId;

        try {
            return $callback();
        } finally {
            $this->organizationId = $previous;
        }
    }
}
