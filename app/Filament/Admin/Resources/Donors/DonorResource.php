<?php

namespace App\Filament\Admin\Resources\Donors;

use App\Filament\Admin\Resources\Donors\Pages\CreateDonor;
use App\Filament\Admin\Resources\Donors\Pages\EditDonor;
use App\Filament\Admin\Resources\Donors\Pages\ListDonors;
use App\Filament\Admin\Resources\Donors\Schemas\DonorForm;
use App\Filament\Admin\Resources\Donors\Tables\DonorsTable;
use App\Models\Concerns\NationalOrOrganizationScope;
use App\Models\Donor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DonorResource extends Resource
{
    protected static ?string $model = Donor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'bailleur national';

    protected static ?string $pluralModelLabel = 'bailleurs nationaux';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels nationaux';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DonorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonorsTable::configure($table);
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
            'index' => ListDonors::route('/'),
            'create' => CreateDonor::route('/create'),
            'edit' => EditDonor::route('/{record}/edit'),
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
