<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Priorité d'une tâche (RGT-01).
 */
enum TaskPriority: string implements HasColor, HasLabel
{
    case Basse = 'basse';
    case Normale = 'normale';
    case Haute = 'haute';
    case Urgente = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Basse => 'Basse',
            self::Normale => 'Normale',
            self::Haute => 'Haute',
            self::Urgente => 'Urgente',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Basse => 'gray',
            self::Normale => 'info',
            self::Haute => 'warning',
            self::Urgente => 'danger',
        };
    }
}
