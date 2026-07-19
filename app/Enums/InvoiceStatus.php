<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'une facture (RGF-06).
 */
enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À payer',
            self::Paid => 'Payée',
            self::Failed => 'Échec',
            self::Void => 'Annulée',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Void => 'gray',
        };
    }
}
