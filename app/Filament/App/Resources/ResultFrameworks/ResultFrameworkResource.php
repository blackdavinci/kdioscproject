<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ResultFrameworks;

use App\Filament\App\Resources\ResultFrameworks\Pages\CreateResultFramework;
use App\Filament\App\Resources\ResultFrameworks\Pages\EditResultFramework;
use App\Filament\App\Resources\ResultFrameworks\Pages\ListResultFrameworks;
use App\Filament\App\Resources\ResultFrameworks\Schemas\ResultFrameworkForm;
use App\Filament\App\Resources\ResultFrameworks\Tables\ResultFrameworksTable;
use App\Models\ResultFramework;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ResultFrameworkResource extends Resource
{
    protected static ?string $model = ResultFramework::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $modelLabel = 'cadre de résultats';

    protected static ?string $pluralModelLabel = 'cadres de résultats';

    protected static string|UnitEnum|null $navigationGroup = 'Suivi-évaluation';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ResultFrameworkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResultFrameworksTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se', 'chef_projet']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResultFrameworks::route('/'),
            'create' => CreateResultFramework::route('/create'),
            'edit' => EditResultFramework::route('/{record}/edit'),
        ];
    }
}
