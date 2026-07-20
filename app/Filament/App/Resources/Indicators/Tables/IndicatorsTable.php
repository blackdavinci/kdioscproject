<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IndicatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.title')
                    ->label('Projet')
                    ->badge(),
                TextColumn::make('logframeNode.code')
                    ->label('Niveau')
                    ->placeholder('—'),
                TextColumn::make('unit')
                    ->label('Unité')
                    ->placeholder('—'),
                TextColumn::make('period_type')
                    ->label('Période')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('project')
                    ->label('Projet')
                    ->relationship('project', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
