<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cycle de vie d'un abonnement (RGF-05).
 */
enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Grace = 'grace';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Essai',
            self::Active => 'Actif',
            self::PastDue => 'Échu',
            self::Grace => 'Délai de grâce',
            self::Suspended => 'Suspendu',
            self::Cancelled => 'Résilié',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Trial => 'info',
            self::Active => 'success',
            self::PastDue => 'warning',
            self::Grace => 'warning',
            self::Suspended => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /**
     * L'abonnement donne-t-il accès à la plateforme ?
     */
    public function grantsAccess(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::PastDue, self::Grace], true);
    }
}
