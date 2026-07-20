<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BudgetLines;

use App\Filament\App\Resources\BudgetLines\Pages\CreateBudgetLine;
use App\Filament\App\Resources\BudgetLines\Pages\EditBudgetLine;
use App\Filament\App\Resources\BudgetLines\Pages\ListBudgetLines;
use App\Filament\App\Resources\BudgetLines\Schemas\BudgetLineForm;
use App\Filament\App\Resources\BudgetLines\Tables\BudgetLinesTable;
use App\Models\BudgetLine;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BudgetLineResource extends Resource
{
    protected static ?string $model = BudgetLine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'ligne budgétaire';

    protected static ?string $pluralModelLabel = 'lignes budgétaires';

    protected static string|UnitEnum|null $navigationGroup = 'Budget';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return BudgetLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetLinesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_financier', 'chef_projet']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetLines::route('/'),
            'create' => CreateBudgetLine::route('/create'),
            'edit' => EditBudgetLine::route('/{record}/edit'),
        ];
    }
}
