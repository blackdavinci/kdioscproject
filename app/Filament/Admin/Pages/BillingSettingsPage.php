<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Settings\BillingSettings;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Configuration de la facturation (RGF-10/12) : identifiants Djomy (chiffrés) et
 * politique d'abonnement (délai de grâce, relances, réactivation automatique).
 */
class BillingSettingsPage extends SettingsPage
{
    protected static string $settings = BillingSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configuration facturation';

    protected static ?string $title = 'Configuration de la facturation';

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 9;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Encaissement Djomy')
                ->description('Mobile money (Orange Money, MTN, Moov). Les clés sont chiffrées au repos.')
                ->columns(2)
                ->schema([
                    Toggle::make('djomy_enabled')
                        ->label('Activer le paiement en ligne')
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('djomy_environment')
                        ->label('Environnement')
                        ->helperText('sandbox ou production')
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('djomy_client_id')
                        ->label('Client ID')
                        ->password()
                        ->revealable()
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('djomy_client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('djomy_partner_domain')
                        ->label('Partner Domain (X-PARTNER-DOMAIN)')
                        ->password()
                        ->revealable()
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('djomy_webhook_secret')
                        ->label('Webhook Secret (optionnel)')
                        ->password()
                        ->revealable()
                        ->helperText('Laissez vide : Djomy signe avec le Client Secret.')
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('djomy_api_url')
                        ->label('URL API (optionnel)')
                        ->placeholder('https://api.djomy.africa/v1/')
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled')),
                    TextInput::make('webhook_url_display')
                        ->label('URL de webhook à configurer chez Djomy')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(url('/webhooks/djomy'))
                        ->visible(fn (Get $get): bool => (bool) $get('djomy_enabled'))
                        ->columnSpanFull(),
                ]),

            Section::make('Politique d’abonnement')
                ->columns(2)
                ->schema([
                    TextInput::make('grace_days')
                        ->label('Délai de grâce (jours)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Toggle::make('auto_reactivate_on_payment')
                        ->label('Réactivation automatique au paiement'),
                    TagsInput::make('reminder_days_before')
                        ->label('Relances (jours avant échéance)')
                        ->placeholder('30, 7, 0')
                        ->helperText('Nombre de jours avant l’échéance déclenchant une relance.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['reminder_days_before']) && is_array($data['reminder_days_before'])) {
            $data['reminder_days_before'] = array_values(array_map('intval', $data['reminder_days_before']));
        }

        return $data;
    }
}
