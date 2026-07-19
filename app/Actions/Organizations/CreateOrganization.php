<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Actions\Invitations\SendInvitation;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Crée une organisation et invite immédiatement son premier administrateur
 * (story 1.1, RG-01). Opéré par le super-admin depuis le panel `admin`, hors tenancy.
 * L'organisation est isolée dès sa création ; l'admin reçoit un lien d'activation 72 h.
 *
 * @phpstan-type OrganizationAttributes array<string, mixed>
 */
class CreateOrganization
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{organization: Organization, invitation: ?Invitation}
     */
    public function handle(array $attributes, string $adminEmail): array
    {
        return DB::transaction(function () use ($attributes, $adminEmail): array {
            $organization = Organization::create($attributes);

            $invitation = (new SendInvitation)->handle($organization, $adminEmail, UserRole::Admin);

            return ['organization' => $organization, 'invitation' => $invitation];
        });
    }
}
