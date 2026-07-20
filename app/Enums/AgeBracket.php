<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tranches d'âge fixes V1 pour la désagrégation des participants (RGA-05).
 */
enum AgeBracket: string implements HasLabel
{
    case ZeroToFive = '0_5';
    case SixToFourteen = '6_14';
    case FifteenToTwentyFour = '15_24';
    case TwentyFiveToFiftyNine = '25_59';
    case SixtyPlus = '60_plus';

    public function label(): string
    {
        return match ($this) {
            self::ZeroToFive => '0–5 ans',
            self::SixToFourteen => '6–14 ans',
            self::FifteenToTwentyFour => '15–24 ans',
            self::TwentyFiveToFiftyNine => '25–59 ans',
            self::SixtyPlus => '60 ans et +',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
