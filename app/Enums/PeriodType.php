<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Type de périodisation d'un indicateur (RGSE-05).
 */
enum PeriodType: string implements HasLabel
{
    case Mensuel = 'mensuel';
    case Trimestriel = 'trimestriel';
    case Annuel = 'annuel';

    public function label(): string
    {
        return match ($this) {
            self::Mensuel => 'Mensuel',
            self::Trimestriel => 'Trimestriel',
            self::Annuel => 'Annuel',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
