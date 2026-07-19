<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Sectors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SectorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nom du secteur')
                        ->required()
                        ->maxLength(255),
                ]),
        ]);
    }
}
