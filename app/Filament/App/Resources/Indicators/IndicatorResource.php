<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators;

use App\Filament\App\Resources\Indicators\Pages\CreateIndicator;
use App\Filament\App\Resources\Indicators\Pages\EditIndicator;
use App\Filament\App\Resources\Indicators\Pages\ListIndicators;
use App\Filament\App\Resources\Indicators\RelationManagers\TargetsRelationManager;
use App\Filament\App\Resources\Indicators\RelationManagers\ValuesRelationManager;
use App\Filament\App\Resources\Indicators\Schemas\IndicatorForm;
use App\Filament\App\Resources\Indicators\Tables\IndicatorsTable;
use App\Models\Indicator;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorResource extends Resource
{
    protected static ?string $model = Indicator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $modelLabel = 'indicateur';

    protected static ?string $pluralModelLabel = 'indicateurs';

    protected static string|UnitEnum|null $navigationGroup = 'Suivi-évaluation';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return IndicatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndicatorsTable::configure($table);
    }

    /** S&E et direction gèrent les indicateurs ; le bailleur n'y accède pas. */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se', 'chef_projet']);
    }

    public static function getRelations(): array
    {
        return [
            TargetsRelationManager::class,
            ValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicators::route('/'),
            'create' => CreateIndicator::route('/create'),
            'edit' => EditIndicator::route('/{record}/edit'),
        ];
    }
}
