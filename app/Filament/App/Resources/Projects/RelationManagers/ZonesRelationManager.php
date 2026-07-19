<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\RelationManagers;

use App\Models\GeoUnit;
use App\Models\Locality;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = 'Zone d’intervention';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('geo_unit_id')
                ->label('Unité administrative (référentiel national)')
                ->options(fn (): array => GeoUnit::query()->where('active', true)->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                ->getSearchResultsUsing(fn (string $search): array => GeoUnit::query()->where('active', true)->where('name', 'ilike', "%{$search}%")->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                ->searchable()
                ->helperText('Ou une localité de votre organisation ci-dessous.'),
            Select::make('locality_id')
                ->label('Localité de l’organisation')
                ->options(fn (): array => Locality::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('geoUnit.name')
                    ->label('Unité administrative')
                    ->placeholder('—'),
                TextColumn::make('locality.name')
                    ->label('Localité')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une zone'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
