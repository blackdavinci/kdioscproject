<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\RelationManagers;

use App\Enums\ProjectStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusChanges';

    protected static ?string $title = 'Historique de statut';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('from_status')
                    ->label('De')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?ProjectStatus $state): string => $state?->label() ?? '—'),
                TextColumn::make('to_status')
                    ->label('Vers')
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->label()),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('changedBy.email')
                    ->label('Par')
                    ->placeholder('—'),
            ]);
    }
}
