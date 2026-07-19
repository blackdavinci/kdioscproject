<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureSuperAdminTwoFactor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Panel super-admin plateforme (KIDIANI), hors tenancy — RG-14.
            ->id('admin')
            ->path('admin')
            ->authGuard('platform')
            ->login()
            ->brandName('KIDIANI OSC — Administration')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->plugins([
                // Santé de la plateforme et sauvegardes (§5, écran 10).
                FilamentSpatieLaravelHealthPlugin::make(),
                FilamentSpatieLaravelBackupPlugin::make(),
                // Mon profil (nom, mot de passe, photo) + 2FA obligatoire pour le
                // super-admin (RG-09), imposée via EnsureSuperAdminTwoFactor plutôt
                // que le « force » de Breezy afin de garder toutes les sections visibles.
                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, userMenuLabel: 'Mon profil')
                    ->enableTwoFactorAuthentication(),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureSuperAdminTwoFactor::class,
            ]);
    }
}
