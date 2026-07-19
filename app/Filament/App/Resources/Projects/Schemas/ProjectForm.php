<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Schemas;

use App\Models\Donor;
use App\Models\Project;
use App\Tenancy\TenantContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                // Lecture seule quand le projet est suspendu ou clôturé (RGP-07).
                ->disabled(fn (?Project $record): bool => $record?->isReadOnly() ?? false)
                ->schema([
                    Fieldset::make('Identité')
                        ->columns(2)
                        ->schema([
                            TextInput::make('title')
                                ->label('Titre du projet')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('code')
                                ->label('Code')
                                ->required()
                                ->maxLength(50)
                                ->helperText('Unique au sein de votre organisation.')
                                ->unique(
                                    table: 'projects',
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn ($rule) => $rule->where('organization_id', app(TenantContext::class)->id()),
                                ),
                        ]),

                    Fieldset::make('Description')
                        ->schema([
                            Textarea::make('description')
                                ->label('Description')
                                ->rows(3)
                                ->columnSpanFull(),
                            Textarea::make('target_groups')
                                ->label('Groupes cibles')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),

                    Fieldset::make('Période')
                        ->columns(2)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Date de début')
                                ->native(false)
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Date de fin')
                                ->native(false)
                                ->required()
                                ->afterOrEqual('start_date'),
                        ]),

                    Fieldset::make('Secteurs d’intervention')
                        ->schema([
                            Select::make('sectors')
                                ->label('Secteurs')
                                ->relationship('sectors', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->columnSpanFull(),
                        ]),

                    Fieldset::make('Bailleurs et montants')
                        ->schema([
                            Repeater::make('donors')
                                ->label('Financements')
                                ->relationship('donors')
                                ->columnSpanFull()
                                ->columns(3)
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
                                        ->default(0)
                                        ->required(),
                                    TextInput::make('amount_foreign')
                                        ->label('Montant devise (informatif)')
                                        ->numeric()
                                        ->helperText('Facultatif — non converti.'),
                                ])
                                ->addActionLabel('Ajouter un bailleur')
                                ->defaultItems(0),
                        ]),
                ]),
        ]);
    }
}
