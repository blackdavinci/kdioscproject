<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Colonnes du kanban des tâches (RGT-03). Transitions libres au glisser-déposer.
 */
enum TaskStatus: string implements HasColor, HasLabel
{
    case AFaire = 'a_faire';
    case EnCours = 'en_cours';
    case Bloque = 'bloque';
    case Termine = 'termine';

    public function label(): string
    {
        return match ($this) {
            self::AFaire => 'À faire',
            self::EnCours => 'En cours',
            self::Bloque => 'Bloqué',
            self::Termine => 'Terminé',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AFaire => 'gray',
            self::EnCours => 'info',
            self::Bloque => 'danger',
            self::Termine => 'success',
        };
    }
}
