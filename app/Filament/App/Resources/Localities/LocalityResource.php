<?php

namespace App\Filament\App\Resources\Localities;

use App\Filament\App\Resources\Localities\Pages\CreateLocality;
use App\Filament\App\Resources\Localities\Pages\EditLocality;
use App\Filament\App\Resources\Localities\Pages\ListLocalities;
use App\Filament\App\Resources\Localities\Schemas\LocalityForm;
use App\Filament\App\Resources\Localities\Tables\LocalitiesTable;
use App\Models\Locality;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LocalityResource extends Resource
{
    protected static ?string $model = Locality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $modelLabel = 'localité';

    protected static ?string $pluralModelLabel = 'localités';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation';

    protected static ?int $navigationSort = 3;

    /**
     * Création de localités : admin, chef de projet, responsable S&E (matrice §6).
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'chef_projet', 'responsable_se']);
    }

    public static function form(Schema $schema): Schema
    {
        return LocalityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocalitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocalities::route('/'),
            'create' => CreateLocality::route('/create'),
            'edit' => EditLocality::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
