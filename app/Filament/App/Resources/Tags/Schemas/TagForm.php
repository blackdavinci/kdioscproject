<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tags\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom de l’étiquette')
                        ->required()
                        ->maxLength(255),
                    ColorPicker::make('color')
                        ->label('Couleur')
                        ->required(),
                ]),
        ]);
    }
}
