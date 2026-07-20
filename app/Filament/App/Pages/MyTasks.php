<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * « Mes tâches » (RGT-07) : toutes les tâches assignées à l'utilisateur courant,
 * toutes sources confondues, triées par échéance (retards en tête).
 */
class MyTasks extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.my-tasks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Collaboration';

    protected static ?string $navigationLabel = 'Mes tâches';

    protected static ?string $title = 'Mes tâches';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $userId = Filament::auth()->id();

        return $table
            ->query(fn (): Builder => Task::query()
                ->where('assignee_user_id', $userId)
                ->where('status', '!=', TaskStatus::Termine->value))
            // Échéances datées d'abord (les plus proches/dépassées en tête), puis sans échéance.
            ->defaultSort('due_date', 'asc')
            ->emptyStateHeading('Aucune tâche en cours ne vous est assignée')
            ->columns([
                TextColumn::make('title')
                    ->label('Tâche')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.title')
                    ->label('Projet')
                    ->placeholder('Interne')
                    ->badge()
                    ->color(fn (Task $record): string => $record->isInternal() ? 'gray' : 'primary'),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (Task $record): string => $record->isOverdue() ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ]);
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof User;
    }
}
