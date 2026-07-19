<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Plans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom du plan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('amount_gnf')
                        ->label('Prix (GNF)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix('GNF'),
                    Select::make('period')
                        ->label('Périodicité')
                        ->options(['year' => 'Annuel', 'month' => 'Mensuel'])
                        ->default('year')
                        ->required(),
                    TextInput::make('trial_days')
                        ->label('Durée d’essai (jours)')
                        ->numeric()
                        ->minValue(0)
                        ->default(14)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Plan actif')
                        ->default(true),
                ]),
        ]);
    }
}
