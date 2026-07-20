<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Source d'une valeur d'indicateur (RGSE-06) : saisie manuelle ou soumission Kobo
 * (import préparé pour la Spec 07).
 */
enum IndicatorValueSource: string implements HasLabel
{
    case Manuelle = 'manuelle';
    case Kobo = 'kobo';

    public function label(): string
    {
        return match ($this) {
            self::Manuelle => 'Saisie manuelle',
            self::Kobo => 'Soumission Kobo',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
