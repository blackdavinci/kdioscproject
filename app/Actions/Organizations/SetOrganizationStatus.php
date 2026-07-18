<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Enums\OrganizationStatus;
use App\Models\Organization;

/**
 * Suspend ou réactive une organisation (RG-04). La suspension exige un motif et
 * bloque la connexion de tous les membres (appliqué par ApplyTenantState, qui relit
 * le statut en base) sans supprimer aucune donnée ni interrompre les sauvegardes.
 */
class SetOrganizationStatus
{
    public function suspend(Organization $organization, string $reason): void
    {
        $organization->forceFill([
            'status' => OrganizationStatus::Suspended,
            'suspension_reason' => $reason,
        ])->save();
    }

    public function reactivate(Organization $organization): void
    {
        $organization->forceFill([
            'status' => OrganizationStatus::Active,
            'suspension_reason' => null,
        ])->save();
    }
}
