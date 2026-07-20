<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Type d'une écriture budgétaire (RGB-05) : engagement (prévu non payé) ou
 * dépense réalisée (effective).
 */
enum ExpenseKind: string implements HasColor, HasLabel
{
    case Engagement = 'engagement';
    case Realisee = 'realisee';

    public function label(): string
    {
        return match ($this) {
            self::Engagement => 'Engagement',
            self::Realisee => 'Dépense réalisée',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Engagement => 'warning',
            self::Realisee => 'success',
        };
    }
}
