<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\Schemas;

use App\Enums\AgeBracket;
use App\Enums\Sex;
use App\Models\Locality;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BeneficiaryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Identification')
                        ->columns(2)
                        ->schema([
                            TextInput::make('code')
                                ->label('Identifiant')
                                ->required()
                                ->maxLength(50)
                                ->unique(table: 'beneficiaries', column: 'code', ignoreRecord: true),
                        ]),

                    Fieldset::make('Données désagrégées (minimales)')
                        ->columns(2)
                        ->schema([
                            Select::make('sex')->label('Sexe')->options(Sex::class),
                            Select::make('age_bracket')->label('Tranche d’âge')->options(AgeBracket::class),
                            TextInput::make('birth_year')->label('Année de naissance')->numeric()->minValue(1900)->maxValue(2100),
                            Select::make('locality_id')
                                ->label('Localité')
                                ->options(fn (): array => Locality::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable(),
                        ]),

                    Fieldset::make('Nominatifs (confidentiels, chiffrés — jamais exportés)')
                        ->columns(2)
                        ->schema([
                            TextInput::make('full_name')->label('Nom complet')->maxLength(255),
                            TextInput::make('contact')->label('Contact')->maxLength(255),
                            Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
