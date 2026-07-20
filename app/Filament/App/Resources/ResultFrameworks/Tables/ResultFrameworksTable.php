<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ResultFrameworks\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultFrameworksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Cadre')->searchable(),
                TextColumn::make('project.title')->label('Projet')->badge(),
                TextColumn::make('donor.name')->label('Bailleur')->placeholder('Organisation'),
                TextColumn::make('indicators_count')->label('Indicateurs')->counts('indicators')->alignCenter(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
