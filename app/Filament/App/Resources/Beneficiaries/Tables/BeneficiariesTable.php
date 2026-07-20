<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\Tables;

use App\Enums\AgeBracket;
use App\Enums\Sex;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BeneficiariesTable
{
    public static function configure(Table $table): Table
    {
        // Aucune colonne nominative (RGSE-09) : seuls l'identifiant et les agrégats.
        return $table
            ->columns([
                TextColumn::make('code')->label('Identifiant')->searchable()->sortable(),
                TextColumn::make('sex')->label('Sexe')->badge()->placeholder('—'),
                TextColumn::make('age_bracket')->label('Âge')->badge()->placeholder('—'),
                TextColumn::make('locality.name')->label('Localité')->placeholder('—'),
                TextColumn::make('activities_count')->label('Participations')->counts('activities')->alignCenter(),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('sex')->label('Sexe')->options(Sex::class),
                SelectFilter::make('age_bracket')->label('Tranche d’âge')->options(AgeBracket::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
