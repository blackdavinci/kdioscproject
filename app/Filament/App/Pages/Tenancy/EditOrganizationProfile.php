<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Tenancy;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres de l'organisation (§5-1) : profil, sous-domaine, devise/année fiscale et
 * préférences de notification. Édite l'organisation courante (tenant). Réservé à l'admin.
 */
class EditOrganizationProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Paramètres de l’organisation';
    }

    public static function canView(Model $tenant): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('admin');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profil')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom de l’organisation')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sigle')
                        ->label('Sigle')
                        ->maxLength(50),
                    TextInput::make('contacts.email')
                        ->label('E-mail de contact')
                        ->email(),
                    TextInput::make('contacts.phone')
                        ->label('Téléphone de contact')
                        ->tel(),
                    SpatieMediaLibraryFileUpload::make('logo')
                        ->label('Logo')
                        ->collection('logo')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            Section::make('Adresse en ligne')
                ->schema([
                    TextInput::make('slug')
                        ->label('Sous-domaine dédié')
                        ->helperText('Adresse de votre espace : {sous-domaine}.'.(string) config('app.tenant_domain'))
                        ->prefix('https://')
                        ->suffix('.'.(string) config('app.tenant_domain'))
                        ->required()
                        ->alphaDash()
                        ->unique(table: 'organizations', column: 'slug', ignoreRecord: true),
                ]),

            Section::make('Paramètres')
                ->columns(2)
                ->schema([
                    TextInput::make('currency')
                        ->label('Devise d’affichage')
                        ->default('GNF')
                        ->required()
                        ->maxLength(3),
                    Select::make('fiscal_year_start')
                        ->label('Mois de début d’année fiscale')
                        ->options([
                            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                        ])
                        ->default(1)
                        ->required(),
                ]),

            Section::make('Notifications')
                ->description('Modèle centralisé : les e-mails partent du compte plateforme au nom de votre organisation.')
                ->columns(2)
                ->schema([
                    TextInput::make('settings.notifications.from_name')
                        ->label('Nom d’expéditeur affiché')
                        ->placeholder('Par défaut : le nom de l’organisation'),
                    TextInput::make('settings.notifications.reply_to')
                        ->label('Adresse de réponse')
                        ->email(),
                    Toggle::make('settings.notifications.sms_enabled')
                        ->label('Activer les SMS'),
                    TextInput::make('settings.notifications.sms_monthly_quota')
                        ->label('Quota SMS mensuel')
                        ->numeric()
                        ->minValue(0),
                ]),
        ]);
    }
}
