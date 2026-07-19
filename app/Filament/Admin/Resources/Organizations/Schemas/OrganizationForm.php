<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Organizations\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité')
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

                Section::make('Paramètres')
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

                Section::make('Premier administrateur')
                    ->description('Un lien d’activation valable 72 h lui sera envoyé par e-mail (RG-07).')
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('admin_email')
                            ->label('E-mail du premier administrateur')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
