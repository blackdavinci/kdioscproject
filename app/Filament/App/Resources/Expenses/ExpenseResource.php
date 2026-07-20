<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Expenses;

use App\Filament\App\Resources\Expenses\Pages\CreateExpense;
use App\Filament\App\Resources\Expenses\Pages\EditExpense;
use App\Filament\App\Resources\Expenses\Pages\ListExpenses;
use App\Filament\App\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\App\Resources\Expenses\Tables\ExpensesTable;
use App\Models\Expense;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $modelLabel = 'dépense';

    protected static ?string $pluralModelLabel = 'dépenses';

    protected static string|UnitEnum|null $navigationGroup = 'Budget';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_financier']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
