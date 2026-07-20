<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\Tenancy\EditOrganizationProfile;
use App\Http\Middleware\ApplyTenantState;
use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Models\Organization;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantProfile(EditOrganizationProfile::class)
            ->tenantMiddleware([
                ApplyTenantState::class,
                EnsureAdminTwoFactor::class,
            ], isPersistent: true)
            ->plugins([
                // Mon profil + 2FA (proposée à tous, imposée aux admins via EnsureAdminTwoFactor, RG-09).
                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, userMenuLabel: 'Mon profil')
                    ->enableTwoFactorAuthentication(),
            ])
            // Bandeau persistant d'accès d'assistance (RG-14).
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('filament.app.assistance-banner')->render(),
            )
            ->brandName('KIDIANI OSC')
            // Carte des interventions (RGA-11) : Leaflet bundlé, chargé à la demande.
            ->assets([
                Js::make('kdi-map', Vite::asset('resources/js/map.js')),
                Js::make('kdi-kanban', Vite::asset('resources/js/kanban.js')),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
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
            ]);
    }
}
