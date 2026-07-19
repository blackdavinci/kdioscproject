<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Niveau administratif COD-AB (RG-21). L'ADM4 (quartier/district) n'est pas
 * utilisé sur la plateforme. Stocké en entier dans geo_units.level ; cet enum
 * ne sert qu'à l'affichage et aux options de formulaire (le modèle n'est pas casté).
 */
enum GeoLevel: int implements HasColor, HasLabel
{
    case Region = 1;
    case Prefecture = 2;
    case Commune = 3;

    public function label(): string
    {
        return match ($this) {
            self::Region => 'Région',
            self::Prefecture => 'Préfecture',
            self::Commune => 'Commune',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Region => 'info',
            self::Prefecture => 'warning',
            self::Commune => 'gray',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Region->value => self::Region->label(),
            self::Prefecture->value => self::Prefecture->label(),
            self::Commune->value => self::Commune->label(),
        ];
    }
}
