<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Tables;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Activité')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.title')
                    ->label('Projet')
                    ->searchable(),
                TextColumn::make('logframeNode.code')
                    ->label('Cadre logique')
                    ->placeholder('—'),
                TextColumn::make('planned_start')
                    ->label('Prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('realized_at')
                    ->label('Réalisée')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->state(fn (Activity $record): string => $record->responsibleName()),
            ])
            ->defaultSort('planned_start', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ActivityStatus::class),
                SelectFilter::make('project')
                    ->label('Projet')
                    ->relationship('project', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
