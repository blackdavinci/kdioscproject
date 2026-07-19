<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\GeoUnit;
use App\Models\Locality;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

/**
 * Écran super-admin du référentiel géographique national COD-AB (§5, écran 9).
 * Affiche l'état du référentiel et permet de (re)lancer l'import idempotent (RG-22).
 */
class GeoReferential extends Page
{
    protected string $view = 'filament.admin.pages.geo-referential';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Référentiel géographique';

    protected static ?string $title = 'Référentiel géographique national';

    protected static ?int $navigationSort = 8;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Lancer l’import COD-AB')
                ->icon('heroicon-o-arrow-down-tray')
                ->requiresConfirmation()
                ->modalDescription('Import idempotent par P-code : ajouts et renommages appliqués, aucune suppression (RG-22).')
                ->action(function (): void {
                    Artisan::call('geo:import');

                    Notification::make()
                        ->success()
                        ->title('Import terminé')
                        ->body(trim(Artisan::output()))
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function getViewData(): array
    {
        return [
            'total' => GeoUnit::count(),
            'regions' => GeoUnit::where('level', 1)->count(),
            'prefectures' => GeoUnit::where('level', 2)->count(),
            'communes' => GeoUnit::where('level', 3)->count(),
            'localities' => Locality::withoutGlobalScopes()->count(),
        ];
    }
}
