<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks\Tables;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.title')
                    ->label('Projet')
                    ->placeholder('Interne')
                    ->badge()
                    ->color(fn (Task $record): string => $record->isInternal() ? 'gray' : 'primary'),
                TextColumn::make('assignee')
                    ->label('Assigné')
                    ->state(fn (Task $record): string => $record->assigneeName()),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (Task $record): string => $record->isOverdue() ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(TaskStatus::class),
                SelectFilter::make('priority')->label('Priorité')->options(TaskPriority::class),
                SelectFilter::make('project')->label('Projet')->relationship('project', 'title'),
                Filter::make('internal')
                    ->label('Hors projet')
                    ->query(fn (Builder $query): Builder => $query->whereNull('project_id')->whereNull('activity_id')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
