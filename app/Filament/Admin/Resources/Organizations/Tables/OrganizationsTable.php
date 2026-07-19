<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Organizations\Tables;

use App\Actions\Organizations\SetOrganizationStatus;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Organisation')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sigle')
                    ->label('Sigle')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Organization $record): bool => $record->isActive())
                    ->schema([
                        Textarea::make('reason')
                            ->label('Motif de la suspension')
                            ->required(),
                    ])
                    ->action(function (Organization $record, array $data): void {
                        (new SetOrganizationStatus)->suspend($record, (string) $data['reason']);

                        Notification::make()->success()->title('Organisation suspendue')->send();
                    }),

                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Organization $record): bool => ! $record->isActive())
                    ->action(function (Organization $record): void {
                        (new SetOrganizationStatus)->reactivate($record);

                        Notification::make()->success()->title('Organisation réactivée')->send();
                    }),
            ]);
    }
}
