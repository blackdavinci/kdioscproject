<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Type d'un nœud du cadre logique (RGP-08). Hiérarchie attendue :
 * objectif général › objectifs spécifiques › résultats › activités.
 */
enum LogframeNodeType: string implements HasColor, HasLabel
{
    case ObjectifGeneral = 'objectif_general';
    case ObjectifSpecifique = 'objectif_specifique';
    case Resultat = 'resultat';
    case Activite = 'activite';

    public function label(): string
    {
        return match ($this) {
            self::ObjectifGeneral => 'Objectif général',
            self::ObjectifSpecifique => 'Objectif spécifique',
            self::Resultat => 'Résultat',
            self::Activite => 'Activité',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ObjectifGeneral => 'primary',
            self::ObjectifSpecifique => 'info',
            self::Resultat => 'success',
            self::Activite => 'gray',
        };
    }

    /** Préfixe de code proposé automatiquement (RGP-09). */
    public function codePrefix(): string
    {
        return match ($this) {
            self::ObjectifGeneral => 'OG',
            self::ObjectifSpecifique => 'OS',
            self::Resultat => 'R',
            self::Activite => 'A',
        };
    }
}
