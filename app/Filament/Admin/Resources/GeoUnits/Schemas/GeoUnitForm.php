<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GeoUnits\Schemas;

use App\Enums\GeoLevel;
use App\Models\GeoUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class GeoUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Identification')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nom')
                                ->required()
                                ->maxLength(255),
                        ]),

                    Fieldset::make('Hiérarchie')
                        ->schema([
                            Select::make('level')
                                ->label('Niveau')
                                ->options(GeoLevel::options())
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('parent_id', null)),
                            Select::make('parent_id')
                                ->label('Rattachée à')
                                ->helperText('Unité de niveau immédiatement supérieur.')
                                ->options(fn (Get $get): array => self::parentOptions($get('level')))
                                ->searchable()
                                ->preload()
                                ->visible(fn (Get $get): bool => (int) $get('level') > 1)
                                ->required(fn (Get $get): bool => (int) $get('level') > 1),
                        ]),

                    Fieldset::make('Position et statut')
                        ->schema([
                            TextInput::make('center_lat')
                                ->label('Latitude du centre')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90),
                            TextInput::make('center_lon')
                                ->label('Longitude du centre')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180),
                            Toggle::make('active')
                                ->label('Active')
                                ->helperText('Une unité retirée reste en base mais désactivée (RG-22).')
                                ->default(true),
                        ]),
                ]),
        ]);
    }

    /**
     * Parents possibles : les unités du niveau immédiatement supérieur.
     *
     * @return array<string, string>
     */
    protected static function parentOptions(mixed $level): array
    {
        $level = (int) $level;

        if ($level <= 1) {
            return [];
        }

        return GeoUnit::query()
            ->where('level', $level - 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
