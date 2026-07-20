<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\RelationManagers;

use App\Enums\IndicatorValueSource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'Valeurs réalisées';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('period_label')
                ->label('Période')
                ->placeholder('2026-T1')
                ->required()
                ->maxLength(50),
            TextInput::make('value')
                ->label('Valeur réalisée')
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
            Select::make('source')
                ->label('Source')
                ->options(IndicatorValueSource::class)
                ->default(IndicatorValueSource::Manuelle)
                ->required()
                ->live(),
            TextInput::make('kobo_reference')
                ->label('Référence Kobo')
                ->visible(fn (Get $get): bool => $get('source') === IndicatorValueSource::Kobo->value),
            SpatieMediaLibraryFileUpload::make('verification')
                ->label('Moyen de vérification')
                ->collection('verification')
                ->multiple()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                ->maxSize(10240)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_label')
            ->defaultSort('period_start')
            ->columns([
                TextColumn::make('period_label')->label('Période'),
                TextColumn::make('value')->label('Réalisé')->numeric(),
                TextColumn::make('source')->label('Source')->badge(),
                TextColumn::make('recorded_at')->label('Saisie le')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Saisir une valeur')
                    ->mutateDataUsing(function (array $data): array {
                        $data['recorded_by'] = Filament::auth()->id();
                        $data['recorded_at'] = now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
