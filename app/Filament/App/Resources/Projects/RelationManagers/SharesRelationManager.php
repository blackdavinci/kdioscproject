<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\RelationManagers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SharesRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    protected static ?string $title = 'Partages bailleur';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Compte bailleur')
                ->options(fn (): array => User::query()->get()
                    ->filter(fn (User $u): bool => $u->hasRole('bailleur'))
                    ->mapWithKeys(fn (User $u): array => [$u->id => $u->getFilamentName()])
                    ->all())
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.email')
                    ->label('Bailleur'),
                IconColumn::make('active')
                    ->label('Actif')
                    ->state(fn (ProjectShare $record): bool => $record->isActive())
                    ->boolean(),
                TextColumn::make('shared_at')
                    ->label('Partagé le')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('revoked_at')
                    ->label('Révoqué le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Partager à un bailleur')
                    ->visible(fn (): bool => $this->ownerIsShareable())
                    ->mutateDataUsing(function (array $data): array {
                        $data['shared_at'] = now();
                        $data['shared_by'] = Filament::auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProjectShare $record): bool => $record->isActive())
                    ->action(fn (ProjectShare $record) => $record->update(['revoked_at' => now()])),

                Action::make('reshare')
                    ->label('Repartager')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (ProjectShare $record): bool => ! $record->isActive() && $this->ownerIsShareable())
                    ->action(fn (ProjectShare $record) => $record->update(['revoked_at' => null, 'shared_at' => now()])),
            ]);
    }

    /** Le partage n'est possible qu'à partir de l'état « validé » (RGP-15). */
    protected function ownerIsShareable(): bool
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof Project && $owner->status !== ProjectStatus::Brouillon;
    }
}
