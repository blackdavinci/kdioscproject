<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Activity;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Carte des interventions géolocalisées (story 3.6, RGA-11) : points des activités
 * sur fond OpenStreetMap (Leaflet), réservée au responsable S&E et à la direction.
 */
class InterventionsMap extends Page
{
    protected string $view = 'filament.app.pages.interventions-map';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?string $navigationLabel = 'Carte des interventions';

    protected static ?string $title = 'Carte des interventions';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se']);
    }

    /**
     * Points géolocalisés des activités de l'organisation.
     *
     * @return array<int, array{lat: float, lng: float, label: string}>
     */
    public function points(): array
    {
        return Activity::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('project')
            ->get()
            ->map(fn (Activity $a): array => [
                'lat' => (float) $a->latitude,
                'lng' => (float) $a->longitude,
                'label' => (string) e($a->title.' — '.($a->project->title ?? '')),
            ])
            ->all();
    }
}
