<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Organizations\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Identité')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom de l’organisation')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sigle')
                                    ->label('Sigle')
                                    ->maxLength(50),
                                TextInput::make('contacts.email')
                                    ->label('E-mail de contact')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('contacts.phone')
                                    ->label('Téléphone de contact')
                                    ->tel()
                                    ->maxLength(50),
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label('Logo')
                                    ->collection('logo')
                                    ->image()
                                    ->imageEditor()
                                    ->columnSpanFull(),
                            ]),

                        Fieldset::make('Paramètres')
                            ->columns(2)
                            ->schema([
                                TextInput::make('currency')
                                    ->label('Devise d’affichage')
                                    ->default('GNF')
                                    ->required()
                                    ->maxLength(3),
                                TextInput::make('fiscal_year_start')
                                    ->label('Mois de début d’année fiscale')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->default(1)
                                    ->required(),
                            ]),

                        Fieldset::make('Premier administrateur')
                            ->columns(2)
                            ->visibleOn('create')
                            ->schema([
                                TextInput::make('admin_first_name')
                                    ->label('Prénom')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('admin_last_name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('admin_phone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('admin_email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Un lien d’activation valable 72 h lui sera envoyé (RG-07).'),
                            ]),
                    ]),
            ]);
    }
}
