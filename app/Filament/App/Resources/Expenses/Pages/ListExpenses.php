<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Expenses\Pages;

use App\Filament\App\Resources\Expenses\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Saisir une dépense')];
    }
}
