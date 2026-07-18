<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Statut d'une organisation (RG-04). La suspension bloque la connexion de tous
 * ses membres mais conserve les données et n'interrompt pas les sauvegardes.
 */
enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspendue',
        };
    }
}
