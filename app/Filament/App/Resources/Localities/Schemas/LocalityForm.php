<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Localities\Schemas;

use App\Models\GeoUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LocalityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    // Sélecteur géographique national en cascade (RG-23, région → préfecture → sous-préfecture).
                    Select::make('region_id')
                        ->label('Région')
                        ->options(fn (): array => GeoUnit::query()->where('level', 1)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Set $set): void {
                            $set('prefecture_id', null);
                            $set('geo_unit_id', null);
                        }),
                    Select::make('prefecture_id')
                        ->label('Préfecture')
                        ->options(fn (Get $get): array => GeoUnit::query()
                            ->where('level', 2)
                            ->where('parent_id', $get('region_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->disabled(fn (Get $get): bool => blank($get('region_id')))
                        ->afterStateUpdated(fn (Set $set) => $set('geo_unit_id', null)),
                    Select::make('geo_unit_id')
                        ->label('Sous-préfecture / commune')
                        ->options(fn (Get $get): array => GeoUnit::query()
                            ->where('level', 3)
                            ->where('parent_id', $get('prefecture_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get): bool => blank($get('prefecture_id'))),
                    TextInput::make('name')
                        ->label('Nom de la localité (village, quartier)')
                        ->required()
                        ->maxLength(255),
                ]),
        ]);
    }
}
