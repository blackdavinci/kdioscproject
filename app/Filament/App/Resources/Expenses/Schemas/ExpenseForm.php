<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Expenses\Schemas;

use App\Enums\ExpenseKind;
use App\Models\Activity;
use App\Models\BudgetLine;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExpenseForm
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
                                ->required()
                                ->live(),
                            Select::make('budget_line_id')
                                ->label('Ligne budgétaire')
                                ->options(fn (Get $get): array => blank($get('project_id')) ? [] : BudgetLine::query()->where('project_id', $get('project_id'))->orderBy('label')->pluck('label', 'id')->all())
                                ->searchable()
                                ->required(),
                            Select::make('activity_id')
                                ->label('Activité (optionnel)')
                                ->options(fn (Get $get): array => blank($get('project_id')) ? [] : Activity::query()->where('project_id', $get('project_id'))->orderBy('planned_start')->pluck('title', 'id')->all())
                                ->searchable(),
                        ]),

                    Fieldset::make('Écriture')
                        ->columns(2)
                        ->schema([
                            Select::make('kind')
                                ->label('Type')
                                ->options(ExpenseKind::class)
                                ->default(ExpenseKind::Realisee)
                                ->required(),
                            TextInput::make('amount_gnf')
                                ->label('Montant (GNF)')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('label')
                                ->label('Libellé')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            DatePicker::make('spent_on')
                                ->label('Date')
                                ->native(false)
                                ->default(now())
                                ->required(),
                            SpatieMediaLibraryFileUpload::make('justificatif')
                                ->label('Justificatif')
                                ->collection('justificatif')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                ->maxSize(10240)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
