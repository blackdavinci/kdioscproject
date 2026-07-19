<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamMembers\Tables;

use App\Actions\TeamMembers\MergeTeamMembers;
use App\Models\TeamMember;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('function')
                    ->label('Fonction')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->placeholder('—'),
                TextColumn::make('locality.name')
                    ->label('Localité')
                    ->placeholder('—'),
                IconColumn::make('user_id')
                    ->label('Compte lié')
                    ->boolean()
                    ->getStateUsing(fn (TeamMember $record): bool => $record->user_id !== null),
            ])
            ->recordActions([
                EditAction::make(),

                // Fusion d'un doublon vers une autre fiche (RG-16). Seule une fiche sans
                // compte peut être fusionnée (elle est la source archivée).
                Action::make('merge')
                    ->label('Fusionner')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->visible(fn (TeamMember $record): bool => $record->user_id === null)
                    ->schema([
                        Select::make('target_id')
                            ->label('Fusionner vers la fiche')
                            ->options(fn (TeamMember $record): array => TeamMember::query()
                                ->whereKeyNot($record->getKey())
                                ->pluck('full_name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Toutes les références de cette fiche seront réassignées à la fiche cible, puis cette fiche sera archivée.')
                    ->action(function (TeamMember $record, array $data): void {
                        $target = TeamMember::find($data['target_id']);

                        if (! $target instanceof TeamMember) {
                            Notification::make()->danger()->title('Fiche cible introuvable')->send();

                            return;
                        }

                        try {
                            $count = (new MergeTeamMembers)->handle($record, $target);
                            Notification::make()->success()
                                ->title('Fiches fusionnées')
                                ->body("{$count} objet(s) réassigné(s) vers « {$target->full_name} ».")
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()->danger()->title('Fusion impossible')->body($e->getMessage())->send();
                        }
                    }),

                DeleteAction::make(),
            ]);
    }
}
