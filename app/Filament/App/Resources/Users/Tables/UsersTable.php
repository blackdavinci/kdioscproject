<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Users\Tables;

use App\Actions\Invitations\ResendInvitation;
use App\Actions\Users\ChangeUserRole;
use App\Actions\Users\DisableUser;
use App\Actions\Users\ReactivateUser;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\AdministrationException;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teamMember.full_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->getStateUsing(fn (User $record): ?UserRole => self::roleOf($record)),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label('Renvoyer l’invitation')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Invited)
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $invitation = Invitation::query()
                            ->where('organization_id', $record->organization_id)
                            ->where('email', $record->email)
                            ->latest()
                            ->first();

                        if ($invitation instanceof Invitation) {
                            (new ResendInvitation)->handle($invitation);
                        }

                        Notification::make()->success()->title('Invitation renvoyée')->send();
                    }),

                Action::make('changeRole')
                    ->label('Changer le rôle')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Select::make('role')
                            ->label('Nouveau rôle')
                            ->options(UserRole::class)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $role = $data['role'] instanceof UserRole
                            ? $data['role']
                            : UserRole::from((string) $data['role']);
                        try {
                            (new ChangeUserRole)->handle($record, $role);
                            Notification::make()->success()->title('Rôle mis à jour')->send();
                        } catch (AdministrationException $e) {
                            Notification::make()->danger()->title('Action impossible')->body($e->getMessage())->send();
                        }
                    }),

                Action::make('disable')
                    ->label('Désactiver')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Active)
                    ->action(function (User $record): void {
                        try {
                            (new DisableUser)->handle($record);
                            Notification::make()->success()->title('Compte désactivé')->send();
                        } catch (AdministrationException $e) {
                            Notification::make()->danger()->title('Action impossible')->body($e->getMessage())->send();
                        }
                    }),

                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => in_array($record->status, [UserStatus::Disabled, UserStatus::Expired], true))
                    ->action(function (User $record): void {
                        (new ReactivateUser)->handle($record);
                        Notification::make()->success()->title('Compte réactivé')->send();
                    }),
            ]);
    }

    protected static function roleOf(User $user): ?UserRole
    {
        $name = $user->getRoleNames()->first();

        return is_string($name) ? UserRole::tryFrom($name) : null;
    }
}
