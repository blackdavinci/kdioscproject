<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->searchable(),
                TextColumn::make('amount_gnf')
                    ->label('Prix')
                    ->numeric(thousandsSeparator: ' ')
                    ->suffix(' GNF')
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Périodicité')
                    ->formatStateUsing(fn (string $state): string => $state === 'month' ? 'Mensuel' : 'Annuel'),
                TextColumn::make('trial_days')
                    ->label('Essai (j)')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
