<?php

namespace App\Filament\App\Resources\Localities\Pages;

use App\Filament\App\Resources\Localities\LocalityResource;
use App\Models\GeoUnit;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocality extends EditRecord
{
    protected static string $resource = LocalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Pré-remplit la région et la préfecture à partir de la sous-préfecture rattachée,
     * pour que le sélecteur en cascade s'ouvre déjà positionné.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $geoUnitId = $data['geo_unit_id'] ?? null;

        if (is_string($geoUnitId)) {
            $subPrefecture = GeoUnit::find($geoUnitId);
            $prefecture = $subPrefecture?->parent()->first();

            $data['prefecture_id'] = $prefecture?->getKey();
            $data['region_id'] = $prefecture?->parent()->first()?->getKey();
        }

        return $data;
    }
}
