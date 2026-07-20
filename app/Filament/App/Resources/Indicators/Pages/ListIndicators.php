<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Pages;

use App\Filament\App\Resources\Indicators\IndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicators extends ListRecords
{
    protected static string $resource = IndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Créer un indicateur'),
        ];
    }
}
