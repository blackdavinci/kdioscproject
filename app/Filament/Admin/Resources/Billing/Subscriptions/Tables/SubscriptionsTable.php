<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organisation')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plan'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('trial_ends_at')
                    ->label('Fin d’essai')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('current_period_end')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('grace_until')
                    ->label('Grâce jusqu’au')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('current_period_end');
    }
}
