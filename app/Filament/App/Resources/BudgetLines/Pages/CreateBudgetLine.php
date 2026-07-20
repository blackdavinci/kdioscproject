<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BudgetLines\Pages;

use App\Filament\App\Resources\BudgetLines\BudgetLineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudgetLine extends CreateRecord
{
    protected static string $resource = BudgetLineResource::class;
}
