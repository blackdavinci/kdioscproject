<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Sens d'un indicateur (RGSE-01) : croissant = « plus c'est mieux » (le réalisé
 * doit atteindre ou dépasser la cible) ; décroissant = l'inverse.
 */
enum IndicatorDirection: string implements HasLabel
{
    case Croissant = 'croissant';
    case Decroissant = 'decroissant';

    public function label(): string
    {
        return match ($this) {
            self::Croissant => 'Croissant (plus c’est mieux)',
            self::Decroissant => 'Décroissant (moins c’est mieux)',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Taux d'atteinte (0..∞) selon le sens. Null si la cible est nulle/zéro.
     */
    public function attainment(float $realized, ?float $target): ?float
    {
        if ($target === null || $target == 0.0) {
            return null;
        }

        return $this === self::Croissant
            ? $realized / $target
            : $target / max($realized, 0.0001);
    }
}
