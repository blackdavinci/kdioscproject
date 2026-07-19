<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamMembers\Schemas;

use App\Models\Locality;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Nom complet')
                    ->required()
                    ->maxLength(255),
                TextInput::make('function')
                    ->label('Fonction')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->maxLength(50),
                Select::make('locality_id')
                    ->label('Localité')
                    ->options(fn (): array => Locality::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }
}
