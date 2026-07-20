<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BudgetLines\Pages;

use App\Filament\App\Resources\BudgetLines\BudgetLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetLines extends ListRecords
{
    protected static string $resource = BudgetLineResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Créer une ligne')];
    }
}
