<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Moyen de paiement d'un règlement d'abonnement (RGF-07).
 */
enum PaymentMethod: string implements HasLabel
{
    case Djomy = 'djomy';
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Djomy => 'Mobile money (Djomy)',
            self::Transfer => 'Virement',
            self::Cash => 'Espèces',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return [
            self::Transfer->value => self::Transfer->label(),
            self::Cash->value => self::Cash->label(),
        ];
    }
}
