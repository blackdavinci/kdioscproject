<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ResultFrameworks\Pages;

use App\Filament\App\Resources\ResultFrameworks\ResultFrameworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResultFrameworks extends ListRecords
{
    protected static string $resource = ResultFrameworkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Créer un cadre')];
    }
}
