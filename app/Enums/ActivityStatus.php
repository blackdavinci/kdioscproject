<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'exécution d'une activité (RGA-07) : planifiée → réalisée, ou annulée.
 */
enum ActivityStatus: string implements HasColor, HasLabel
{
    case Planifiee = 'planifiee';
    case Realisee = 'realisee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiée',
            self::Realisee => 'Réalisée',
            self::Annulee => 'Annulée',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planifiee => 'info',
            self::Realisee => 'success',
            self::Annulee => 'danger',
        };
    }
}
