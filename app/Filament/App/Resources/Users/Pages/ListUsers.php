<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Users\Pages;

use App\Actions\Invitations\SendInvitation;
use App\Enums\UserRole;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Inviter')
                ->icon('heroicon-o-paper-airplane')
                ->schema([
                    TextInput::make('email')
                        ->label('Adresse e-mail')
                        ->email()
                        ->required(),
                    Select::make('role')
                        ->label('Rôle')
                        ->options(UserRole::class)
                        ->required()
                        ->live(),
                    DatePicker::make('account_expires_at')
                        ->label('Expiration du compte')
                        ->helperText('Obligatoire pour les rôles temporaires (consultant, bailleur) — max +12 mois.')
                        ->minDate(now())
                        ->maxDate(now()->addYear())
                        ->required(fn (Get $get): bool => $this->roleIsTemporary($get('role')))
                        ->visible(fn (Get $get): bool => $this->roleIsTemporary($get('role'))),
                    Select::make('team_member_id')
                        ->label('Rattacher à une fiche membre existante (optionnel)')
                        ->helperText('Évite les doublons : lie le futur compte à une fiche sans compte (RG-16).')
                        ->options(fn (): array => TeamMember::query()
                            ->whereNull('user_id')
                            ->pluck('full_name', 'id')
                            ->all())
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    /** @var Organization $organization */
                    $organization = Filament::getTenant();
                    $sentBy = Filament::auth()->user();

                    $linkTo = null;
                    if (isset($data['team_member_id'])) {
                        $found = TeamMember::find($data['team_member_id']);
                        $linkTo = $found instanceof TeamMember ? $found : null;
                    }

                    $role = $data['role'] instanceof UserRole
                        ? $data['role']
                        : UserRole::from((string) $data['role']);

                    (new SendInvitation)->handle(
                        $organization,
                        (string) $data['email'],
                        $role,
                        $sentBy instanceof User ? $sentBy : null,
                        isset($data['account_expires_at']) ? Carbon::parse((string) $data['account_expires_at']) : null,
                        $linkTo,
                    );

                    // Message générique quelle que soit l'issue réelle (anti-énumération, RG-07).
                    Notification::make()
                        ->success()
                        ->title('Invitation traitée')
                        ->body('Si cette adresse est éligible, une invitation lui a été envoyée.')
                        ->send();
                }),
        ];
    }

    protected function roleIsTemporary(mixed $role): bool
    {
        return is_string($role) && UserRole::tryFrom($role)?->isTemporary() === true;
    }
}
