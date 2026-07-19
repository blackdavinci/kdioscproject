<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Donors\Tables;

use App\Models\Donor;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DonorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Bailleur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sigle')
                    ->label('Sigle')
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('scope')
                    ->label('Portée')
                    ->badge()
                    ->getStateUsing(fn (Donor $record): string => $record->isNational() ? 'Nationale' : 'Propre')
                    ->color(fn (Donor $record): string => $record->isNational() ? 'gray' : 'primary'),
            ])
            ->defaultSort('name')
            ->recordActions([
                // Les bailleurs nationaux sont en lecture seule pour l'organisation (RG-20).
                EditAction::make()->visible(fn (Donor $record): bool => ! $record->isNational()),
                DeleteAction::make()->visible(fn (Donor $record): bool => ! $record->isNational()),
            ]);
    }
}
