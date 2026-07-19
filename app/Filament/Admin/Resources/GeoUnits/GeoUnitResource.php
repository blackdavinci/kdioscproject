<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits;

use App\Filament\Admin\Resources\GeoUnits\Pages\CreateGeoUnit;
use App\Filament\Admin\Resources\GeoUnits\Pages\EditGeoUnit;
use App\Filament\Admin\Resources\GeoUnits\Pages\ListGeoUnits;
use App\Filament\Admin\Resources\GeoUnits\Schemas\GeoUnitForm;
use App\Filament\Admin\Resources\GeoUnits\Tables\GeoUnitsTable;
use App\Models\GeoUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ressource super-admin de curation du référentiel géographique national COD-AB
 * (RG-21/22). Permet les corrections manuelles (ajout de communes manquantes,
 * renommage, coordonnées, activation) en complément de l'import idempotent.
 */
class GeoUnitResource extends Resource
{
    protected static ?string $model = GeoUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $modelLabel = 'unité administrative';

    protected static ?string $pluralModelLabel = 'unités administratives';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel géographique';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GeoUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GeoUnitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeoUnits::route('/'),
            'create' => CreateGeoUnit::route('/create'),
            'edit' => EditGeoUnit::route('/{record}/edit'),
        ];
    }
}
