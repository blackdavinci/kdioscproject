<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Journalise création / modification / suppression d'une entité auditée (RG-26, RGF-16).
 * L'organisation de rattachement (`organization_id`) est déduite du sujet par le modèle
 * {@see ActivityLog}. Les champs sensibles ne sont jamais journalisés
 * (mots de passe, secrets 2FA, jeton d'invitation, réponses brutes de paiement).
 */
trait LogsTenantActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logExcept([
                'password',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'backup_codes',
                'token_hash',
                'djomy_response',
                'updated_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
