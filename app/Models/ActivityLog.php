<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Modèle d'audit personnalisé (RG-26) : ajoute `organization_id` au journal spatie
 * pour scoper la consultation par organisation. L'identifiant est dérivé du sujet de
 * l'activité (ou du tenant courant) à l'enregistrement.
 *
 * @property string|null $organization_id
 */
class ActivityLog extends SpatieActivity
{
    protected $table = 'activity_log';

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $activity): void {
            if ($activity->organization_id !== null) {
                return;
            }

            $activity->organization_id = self::resolveOrganizationId($activity);
        });
    }

    protected static function resolveOrganizationId(ActivityLog $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof Model) {
            $organizationId = $subject->getAttribute('organization_id');

            if (is_string($organizationId)) {
                return $organizationId;
            }

            if ($subject->getTable() === 'organizations') {
                $key = $subject->getKey();

                return is_string($key) ? $key : null;
            }
        }

        return app(TenantContext::class)->id();
    }
}
