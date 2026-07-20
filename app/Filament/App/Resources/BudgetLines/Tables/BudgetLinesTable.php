<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BudgetLines\Tables;

use App\Models\BudgetLine;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')->label('Rubrique')->badge()->placeholder('—'),
                TextColumn::make('label')->label('Ligne')->searchable()->wrap(),
                TextColumn::make('project.title')->label('Projet')->badge(),
                TextColumn::make('amount_gnf')->label('Budget (GNF)')->numeric(thousandsSeparator: ' ')->sortable(),
                TextColumn::make('spent')->label('Dépensé')->state(fn (BudgetLine $r): string => number_format($r->spent(), 0, ',', ' ')),
                TextColumn::make('available')->label('Disponible')
                    ->state(fn (BudgetLine $r): string => number_format($r->available(), 0, ',', ' '))
                    ->color(fn (BudgetLine $r): string => $r->isOverspent() ? 'danger' : 'success'),
                TextColumn::make('rate')->label('Conso')
                    ->state(fn (BudgetLine $r): string => $r->consumptionRate() !== null ? round($r->consumptionRate() * 100).' %' : '—')
                    ->badge()
                    ->color(fn (BudgetLine $r): string => $r->isOverspent() ? 'danger' : ($r->isOverThreshold() ? 'warning' : 'success')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('project')->label('Projet')->relationship('project', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
