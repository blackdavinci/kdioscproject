<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use App\Filament\App\Resources\Projects\Support\ProjectTransition;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('donors.donor.name')
                    ->label('Bailleurs')
                    ->badge()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ProjectStatus::class),
                SelectFilter::make('sectors')
                    ->label('Secteur')
                    ->relationship('sectors', 'name'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('transition')
                    ->label('Changer le statut')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Project $record): bool => $record->status->allowedTransitions() !== [])
                    ->schema([
                        Select::make('to_status')
                            ->label('Nouveau statut')
                            ->options(fn (Project $record): array => collect($record->status->allowedTransitions())
                                ->mapWithKeys(fn (ProjectStatus $s): array => [$s->value => $s->label()])
                                ->all())
                            ->required()
                            ->live(),
                        Textarea::make('reason')
                            ->label('Motif')
                            ->rows(2)
                            ->required(fn (Get $get): bool => ($status = ProjectStatus::tryFrom((string) $get('to_status'))) !== null && $status->requiresReason())
                            ->helperText('Obligatoire pour une suspension ou une clôture.'),
                    ])
                    ->action(fn (Project $record, array $data) => ProjectTransition::apply(
                        $record,
                        ProjectStatus::from($data['to_status']),
                        $data['reason'] ?? null,
                    )),
            ]);
    }
}
