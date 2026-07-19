<?php

namespace App\Filament\Admin\Resources\Sectors;

use App\Filament\Admin\Resources\Sectors\Pages\CreateSector;
use App\Filament\Admin\Resources\Sectors\Pages\EditSector;
use App\Filament\Admin\Resources\Sectors\Pages\ListSectors;
use App\Filament\Admin\Resources\Sectors\Schemas\SectorForm;
use App\Filament\Admin\Resources\Sectors\Tables\SectorsTable;
use App\Models\Concerns\NationalOrOrganizationScope;
use App\Models\Sector;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $modelLabel = 'secteur national';

    protected static ?string $pluralModelLabel = 'secteurs nationaux';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels nationaux';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SectorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectorsTable::configure($table);
    }

    /**
     * Base nationale uniquement (organization_id nul), curée par le super-admin.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(NationalOrOrganizationScope::class)
            ->whereNull('organization_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectors::route('/'),
            'create' => CreateSector::route('/create'),
            'edit' => EditSector::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
                NationalOrOrganizationScope::class,
            ]);
    }
}
