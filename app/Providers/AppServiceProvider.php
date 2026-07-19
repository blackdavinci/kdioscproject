<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Support\CauserResolver;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Contexte de tenant partagé sur toute la durée de la requête / du process.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auteur d'une activité d'audit (RG-26) : un auteur explicite (causedBy) est
        // respecté ; sinon compte tenant (web) ou super-admin (platform).
        app(CauserResolver::class)->resolveUsing(
            fn ($subject) => $subject instanceof Model
                ? $subject
                : (Auth::guard('web')->user() ?? Auth::guard('platform')->user()),
        );

        // Contrôles de santé de la plateforme (panel super-admin, §5 écran 10).
        Health::checks([
            UsedDiskSpaceCheck::new(),
            DatabaseCheck::new(),
            CacheCheck::new(),
            RedisCheck::new(),
            ScheduleCheck::new(),
            DebugModeCheck::new(),
        ]);
    }
}
