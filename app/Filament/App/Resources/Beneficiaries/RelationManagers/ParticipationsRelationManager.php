<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Participations d'un bénéficiaire aux activités (RGSE-11) : alimente le comptage
 * « uniques vs participations ».
 */
class ParticipationsRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Participations aux activités';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Activité')->wrap(),
                TextColumn::make('project.title')->label('Projet')->badge(),
                TextColumn::make('realized_at')->label('Réalisée le')->date('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Rattacher une activité')
                    ->recordSelectSearchColumns(['title']),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
