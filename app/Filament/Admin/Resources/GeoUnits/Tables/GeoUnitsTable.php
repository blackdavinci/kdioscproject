<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Tables;

use App\Enums\GeoLevel;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GeoUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => GeoLevel::from($state)->label())
                    ->color(fn (int $state): string => GeoLevel::from($state)->getColor())
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Rattachée à')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('children_count')
                    ->label('Sous-unités')
                    ->counts('children')
                    ->alignCenter()
                    ->toggleable(),
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options(GeoLevel::options()),
                TernaryFilter::make('active')
                    ->label('Statut')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives')
                    ->placeholder('Toutes'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
