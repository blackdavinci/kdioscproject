<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BudgetLines\Schemas;

use App\Models\BudgetCategory;
use App\Models\Donor;
use App\Models\Project;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BudgetLineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Rattachement')
                        ->columns(2)
                        ->schema([
                            Select::make('project_id')
                                ->label('Projet')
                                ->options(fn (): array => Project::query()->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->required(),
                            Select::make('budget_category_id')
                                ->label('Rubrique')
                                ->options(fn (): array => BudgetCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->createOptionForm([
                                    TextInput::make('name')->label('Nom de la rubrique')->required()->maxLength(255),
                                ])
                                ->createOptionUsing(fn (array $data): string => BudgetCategory::create(['name' => $data['name']])->getKey()),
                            TextInput::make('label')
                                ->label('Libellé de la ligne')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                    Fieldset::make('Montant et seuil')
                        ->columns(2)
                        ->schema([
                            TextInput::make('amount_gnf')
                                ->label('Montant budgété (GNF)')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('threshold_percent')
                                ->label('Seuil d’alerte (%)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(80)
                                ->required(),
                        ]),

                    Fieldset::make('Répartition par bailleur (cofinancement)')
                        ->schema([
                            Repeater::make('allocations')
                                ->label('Répartitions')
                                ->relationship('allocations')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Select::make('donor_id')
                                        ->label('Bailleur')
                                        ->options(fn (): array => Donor::query()->orderBy('name')->pluck('name', 'id')->all())
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('amount_gnf')
                                        ->label('Montant (GNF)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required(),
                                ])
                                ->addActionLabel('Ajouter un bailleur')
                                ->defaultItems(0),
                        ]),
                ]),
        ]);
    }
}
