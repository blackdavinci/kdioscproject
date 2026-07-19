<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Pages;

use App\Filament\Admin\Resources\GeoUnits\GeoUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGeoUnits extends ListRecords
{
    protected static string $resource = GeoUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une unité'),
        ];
    }
}
