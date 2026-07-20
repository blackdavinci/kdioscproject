<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'targets';

    protected static ?string $title = 'Cibles';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('period_label')
                ->label('Période')
                ->placeholder('2026-T1')
                ->required()
                ->maxLength(50),
            TextInput::make('target_value')
                ->label('Valeur cible')
                ->numeric()
                ->required(),
            DatePicker::make('period_start')
                ->label('Début de période')
                ->native(false)
                ->required(),
            DatePicker::make('period_end')
                ->label('Fin de période')
                ->native(false)
                ->required()
                ->afterOrEqual('period_start'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_label')
            ->defaultSort('period_start')
            ->columns([
                TextColumn::make('period_label')->label('Période'),
                TextColumn::make('target_value')->label('Cible')->numeric(),
                TextColumn::make('period_start')->label('Début')->date('d/m/Y'),
                TextColumn::make('period_end')->label('Fin')->date('d/m/Y'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une cible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
