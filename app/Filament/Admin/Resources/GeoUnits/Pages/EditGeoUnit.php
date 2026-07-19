<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Pages;

use App\Filament\Admin\Resources\GeoUnits\GeoUnitResource;
use Filament\Resources\Pages\EditRecord;

class EditGeoUnit extends EditRecord
{
    protected static string $resource = GeoUnitResource::class;
}
