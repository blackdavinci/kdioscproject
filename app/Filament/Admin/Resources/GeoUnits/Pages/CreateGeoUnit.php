<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Pages;

use App\Filament\Admin\Resources\GeoUnits\GeoUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGeoUnit extends CreateRecord
{
    protected static string $resource = GeoUnitResource::class;
}
