<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamMembers\Tables;

use App\Models\TeamMember;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('function')
                    ->label('Fonction')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->placeholder('—'),
                TextColumn::make('locality.name')
                    ->label('Localité')
                    ->placeholder('—'),
                IconColumn::make('user_id')
                    ->label('Compte lié')
                    ->boolean()
                    ->getStateUsing(fn (TeamMember $record): bool => $record->user_id !== null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
