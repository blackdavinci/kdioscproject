<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donors\Schemas;

use App\Enums\DonorType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom du bailleur')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sigle')
                        ->label('Sigle')
                        ->maxLength(50),
                    Select::make('type')
                        ->label('Type')
                        ->options(DonorType::options())
                        ->required(),
                ]),
        ]);
    }
}
