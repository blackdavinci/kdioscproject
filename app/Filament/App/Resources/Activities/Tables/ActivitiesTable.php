<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Tables;

use App\Enums\ActivityStatus;
use App\Filament\App\Resources\Activities\Support\DuplicateActivitySeries;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Activité')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.title')
                    ->label('Projet')
                    ->searchable(),
                TextColumn::make('logframeNode.code')
                    ->label('Cadre logique')
                    ->placeholder('—'),
                TextColumn::make('planned_start')
                    ->label('Prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('realized_at')
                    ->label('Réalisée')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->state(fn (Activity $record): string => $record->responsibleName()),
            ])
            ->defaultSort('planned_start', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ActivityStatus::class),
                SelectFilter::make('project')
                    ->label('Projet')
                    ->relationship('project', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('sheet')
                        ->label('Fiche d’activité (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Activity $record): string => route('activities.sheet', $record), shouldOpenInNewTab: true),
                    Action::make('attendance')
                        ->label('Liste de présence (PDF)')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->url(fn (Activity $record): string => route('activities.attendance', $record), shouldOpenInNewTab: true),
                    Action::make('duplicate')
                        ->label('Dupliquer en série')
                        ->icon('heroicon-o-square-2-stack')
                        ->schema([
                            Select::make('frequency')
                                ->label('Fréquence')
                                ->options([
                                    'weekly' => 'Hebdomadaire',
                                    'biweekly' => 'Toutes les 2 semaines',
                                    'monthly' => 'Mensuelle',
                                ])
                                ->default('weekly')
                                ->required(),
                            TextInput::make('count')
                                ->label('Nombre d’occurrences à générer')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(52)
                                ->default(3)
                                ->required(),
                        ])
                        ->action(function (Activity $record, array $data): void {
                            $n = DuplicateActivitySeries::handle($record, $data['frequency'], (int) $data['count']);
                            Notification::make()->success()->title("{$n} occurrence(s) générée(s)")->send();
                        }),
                ]),
            ]);
    }
}
