<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Sectors\Tables;

use App\Models\Sector;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Secteur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scope')
                    ->label('Portée')
                    ->badge()
                    ->getStateUsing(fn (Sector $record): string => $record->isNational() ? 'Nationale' : 'Propre')
                    ->color(fn (Sector $record): string => $record->isNational() ? 'gray' : 'primary'),
            ])
            ->defaultSort('name')
            ->recordActions([
                // Les entrées nationales sont en lecture seule pour l'organisation (RG-19).
                EditAction::make()->visible(fn (Sector $record): bool => ! $record->isNational()),
                DeleteAction::make()->visible(fn (Sector $record): bool => ! $record->isNational()),
            ]);
    }
}
