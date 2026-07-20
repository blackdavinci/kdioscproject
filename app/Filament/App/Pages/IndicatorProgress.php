<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\IndicatorValue;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Tableau réalisé vs cible (RGSE-07) : par indicateur et par période, cible,
 * réalisé, taux d'atteinte et comparaison visuelle. Rendu serveur (offline).
 *
 * @property string|null $indicatorId
 */
class IndicatorProgress extends Page
{
    protected string $view = 'filament.app.pages.indicator-progress';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Suivi-évaluation';

    protected static ?string $navigationLabel = 'Réalisé vs cible';

    protected static ?string $title = 'Réalisé vs cible';

    protected static ?int $navigationSort = 2;

    public ?string $indicatorId = null;

    public function mount(): void
    {
        $this->indicatorId = $this->indicatorOptions()->keys()->first();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se', 'chef_projet', 'responsable_financier']);
    }

    /**
     * @return Collection<string, string>
     */
    public function indicatorOptions(): Collection
    {
        return Indicator::query()
            ->orderBy('label')
            ->get()
            ->mapWithKeys(fn (Indicator $i): array => [$i->id => trim(($i->code ? $i->code.' — ' : '').$i->label)]);
    }

    public function indicator(): ?Indicator
    {
        return $this->indicatorId === null ? null : Indicator::find($this->indicatorId);
    }

    /**
     * Lignes période par période : cible, réalisé, taux d'atteinte.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $indicator = $this->indicator();
        if (! $indicator instanceof Indicator) {
            return [];
        }

        $targets = $indicator->targets()->get()->keyBy('period_label');
        $values = $indicator->values()->get()->keyBy('period_label');

        $labels = $targets->keys()->merge($values->keys())->unique();

        return $labels->map(function (string $label) use ($indicator, $targets, $values): array {
            $targetRow = $targets->get($label);
            $valueRow = $values->get($label);

            $target = $targetRow instanceof IndicatorTarget ? $targetRow->target_value : null;
            $realized = $valueRow instanceof IndicatorValue ? $valueRow->value : null;
            $start = $targetRow instanceof IndicatorTarget
                ? $targetRow->period_start
                : ($valueRow instanceof IndicatorValue ? $valueRow->period_start : null);

            $attainment = ($target !== null && $realized !== null)
                ? $indicator->direction->attainment((float) $realized, (float) $target)
                : null;

            return [
                'label' => $label,
                'start' => $start,
                'target' => $target,
                'realized' => $realized,
                'attainment' => $attainment,
                'percent' => $attainment !== null ? min(100, round($attainment * 100)) : null,
            ];
        })
            ->sortBy('start')
            ->values()
            ->all();
    }
}
