<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Localities\Tables;

use App\Models\Locality;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocalitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Localité')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('geoUnit.name')
                    ->label('Sous-préfecture / commune')
                    ->sortable(),
                TextColumn::make('rattachement')
                    ->label('Rattachement')
                    ->getStateUsing(fn (Locality $record): string => self::path($record)),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected static function path(Locality $locality): string
    {
        $subPrefecture = $locality->geoUnit;
        $prefecture = $subPrefecture?->parent;
        $region = $prefecture?->parent;

        return collect([$region?->name, $prefecture?->name])
            ->filter()
            ->implode(' › ') ?: '—';
    }
}
