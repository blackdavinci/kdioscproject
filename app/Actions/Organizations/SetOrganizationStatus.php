<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Enums\OrganizationStatus;
use App\Enums\SuspensionSource;
use App\Models\Organization;

/**
 * Suspend ou réactive une organisation (RG-04). La suspension exige un motif et
 * bloque la connexion de tous les membres (appliqué par ApplyTenantState, qui relit
 * le statut en base) sans supprimer aucune donnée ni interrompre les sauvegardes.
 *
 * La `source` distingue une décision du super-admin (`manual`) d'une suspension pour
 * impayé (`billing`) : seule cette dernière est levée automatiquement par un paiement (RGF-11).
 */
class SetOrganizationStatus
{
    public function suspend(
        Organization $organization,
        string $reason,
        SuspensionSource $source = SuspensionSource::Manual,
    ): void {
        $organization->forceFill([
            'status' => OrganizationStatus::Suspended,
            'suspension_reason' => $reason,
            'suspended_source' => $source,
        ])->save();
    }

    public function reactivate(Organization $organization): void
    {
        $organization->forceFill([
            'status' => OrganizationStatus::Active,
            'suspension_reason' => null,
            'suspended_source' => null,
        ])->save();
    }
}
