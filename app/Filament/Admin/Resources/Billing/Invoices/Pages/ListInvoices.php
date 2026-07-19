<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Invoices\Pages;

use App\Filament\Admin\Resources\Billing\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    // Les factures sont émises par le cycle de vie des abonnements, jamais à la main.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
