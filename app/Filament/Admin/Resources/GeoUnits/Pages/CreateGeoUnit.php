<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Pages;

use App\Filament\Admin\Resources\GeoUnits\GeoUnitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateGeoUnit extends CreateRecord
{
    protected static string $resource = GeoUnitResource::class;

    /**
     * Le P-code n'est pas exposé à l'utilisateur mais reste requis (clé unique NOT
     * NULL). Pour un ajout manuel, on génère un code au préfixe distinctif GNX afin
     * qu'il ne collisionne jamais avec un P-code officiel COD-AB lors de l'import.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['pcode'] ??= 'GNX-'.Str::ulid();

        return $data;
    }
}
