<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ResultFrameworks\Schemas;

use App\Models\Donor;
use App\Models\Indicator;
use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ResultFrameworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Cadre')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nom du cadre')
                                ->required()
                                ->maxLength(255),
                            Select::make('project_id')
                                ->label('Projet')
                                ->options(fn (): array => Project::query()->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->required()
                                ->live(),
                            Select::make('donor_id')
                                ->label('Bailleur')
                                ->options(fn (): array => Donor::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->helperText('Un cadre par bailleur ; laisser vide pour un cadre « organisation ».'),
                        ]),

                    Fieldset::make('Indicateurs rattachés')
                        ->schema([
                            Select::make('indicators')
                                ->label('Indicateurs')
                                ->relationship('indicators', 'label')
                                ->options(fn (Get $get): array => Indicator::query()
                                    ->when($get('project_id'), fn ($q, $p) => $q->where('project_id', $p))
                                    ->orderBy('label')
                                    ->pluck('label', 'id')
                                    ->all())
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
