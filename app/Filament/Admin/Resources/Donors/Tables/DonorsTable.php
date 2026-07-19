<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donors\Tables;

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
                    ->label('Bailleur national')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sigle')
                    ->label('Sigle')
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
