<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

/**
 * Fréquence de récurrence d'une tâche (RGT-13). À la clôture d'une occurrence,
 * la suivante est générée avec une échéance décalée.
 */
enum TaskRecurrence: string implements HasLabel
{
    case Aucune = 'aucune';
    case Mensuelle = 'mensuelle';
    case Trimestrielle = 'trimestrielle';
    case Annuelle = 'annuelle';

    public function label(): string
    {
        return match ($this) {
            self::Aucune => 'Aucune',
            self::Mensuelle => 'Mensuelle',
            self::Trimestrielle => 'Trimestrielle',
            self::Annuelle => 'Annuelle',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function isRecurring(): bool
    {
        return $this !== self::Aucune;
    }

    /**
     * Décale une échéance selon la fréquence.
     */
    public function next(Carbon $from): Carbon
    {
        return match ($this) {
            self::Mensuelle => $from->copy()->addMonthNoOverflow(),
            self::Trimestrielle => $from->copy()->addMonthsNoOverflow(3),
            self::Annuelle => $from->copy()->addYearNoOverflow(),
            self::Aucune => $from->copy(),
        };
    }
}
