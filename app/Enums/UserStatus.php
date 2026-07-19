<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cycle de vie d'un compte (§4) : invited → active ⇄ disabled ; active → expired.
 * Toute sortie de `active` révoque les sessions.
 */
enum UserStatus: string implements HasColor, HasLabel
{
    case Invited = 'invited';
    case Active = 'active';
    case Disabled = 'disabled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invité',
            self::Active => 'Actif',
            self::Disabled => 'Désactivé',
            self::Expired => 'Expiré',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Active => 'success',
            self::Disabled => 'gray',
            self::Expired => 'danger',
        };
    }

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
