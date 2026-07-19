<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Actions\Billing\CreateSubscription;
use App\Actions\Invitations\SendInvitation;
use App\Enums\UserRole;
use App\Models\Billing\Plan;
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
    public function handle(
        array $attributes,
        string $adminEmail,
        ?string $adminFullName = null,
        ?string $adminPhone = null,
    ): array {
        return DB::transaction(function () use ($attributes, $adminEmail, $adminFullName, $adminPhone): array {
            // Slug (sous-domaine dédié) dérivé du nom si non fourni.
            if (empty($attributes['slug'])) {
                $base = is_string($attributes['name'] ?? null) ? $attributes['name'] : 'osc';
                $attributes['slug'] = Organization::makeUniqueSlug($base);
            }

            $organization = Organization::create($attributes);

            // Abonnement d'essai créé si un plan actif existe (RGF-04).
            if (Plan::query()->where('is_active', true)->exists()) {
                (new CreateSubscription)->handle($organization);
            }

            $invitation = (new SendInvitation)->handle(
                $organization,
                $adminEmail,
                UserRole::Admin,
                fullName: $adminFullName,
                phone: $adminPhone,
            );

            return ['organization' => $organization, 'invitation' => $invitation];
        });
    }
}
