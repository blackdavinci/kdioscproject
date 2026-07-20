<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Axe de désagrégation « sexe » des participants (RGA-05).
 */
enum Sex: string implements HasLabel
{
    case Femme = 'femme';
    case Homme = 'homme';

    public function label(): string
    {
        return match ($this) {
            self::Femme => 'Femmes',
            self::Homme => 'Hommes',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
