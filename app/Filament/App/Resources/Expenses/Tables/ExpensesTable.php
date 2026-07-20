<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Expenses\Tables;

use App\Enums\ExpenseKind;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('spent_on')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('label')->label('Libellé')->searchable()->wrap(),
                TextColumn::make('budgetLine.label')->label('Ligne')->badge(),
                TextColumn::make('activity.title')->label('Activité')->placeholder('—'),
                TextColumn::make('kind')->label('Type')->badge(),
                TextColumn::make('amount_gnf')->label('Montant (GNF)')->numeric(thousandsSeparator: ' ')->sortable(),
            ])
            ->defaultSort('spent_on', 'desc')
            ->filters([
                SelectFilter::make('kind')->label('Type')->options(ExpenseKind::class),
                SelectFilter::make('project')->label('Projet')->relationship('project', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
