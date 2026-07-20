<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Enums\ActivityStatus;
use App\Enums\DisaggregationDimension;
use App\Enums\Sex;
use App\Models\Activity;
use App\Models\BudgetLine;
use App\Models\Indicator;
use App\Models\Project;
use App\Models\ResultFramework;
use Illuminate\Support\Carbon;

/**
 * Construit les données des rapports (Spec 08) en agrégeant les modules existants.
 * Aucun nominatif de bénéficiaire (RGSE-09). Réutilisé par l'Excel et le PDF.
 */
class ReportBuilder
{
    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, subtitle: string, meta: array<string, string>, headings: list<string>, rows: array<int, array<int, string|int>>, filename: string}
     */
    public static function build(string $type, array $params): array
    {
        return match ($type) {
            'indicators' => self::indicators((string) ($params['framework_id'] ?? '')),
            'financial' => self::financial((string) ($params['project_id'] ?? '')),
            default => self::activities(
                (string) ($params['project_id'] ?? ''),
                $params['period_start'] ?? null,
                $params['period_end'] ?? null,
            ),
        };
    }

    /**
     * @return array{title: string, subtitle: string, meta: array<string, string>, headings: list<string>, rows: array<int, array<int, string|int>>, filename: string}
     */
    private static function activities(string $projectId, mixed $start, mixed $end): array
    {
        $projectTitle = (string) (Project::query()->whereKey($projectId)->value('title') ?? 'Projet');
        $projectCode = (string) (Project::query()->whereKey($projectId)->value('code') ?? $projectId);
        $from = $start ? Carbon::parse((string) $start)->startOfDay() : Carbon::now()->subYear();
        $to = $end ? Carbon::parse((string) $end)->endOfDay() : Carbon::now();

        $activities = Activity::query()
            ->where('project_id', $projectId)
            ->where('status', ActivityStatus::Realisee->value)
            ->whereBetween('realized_at', [$from->toDateString(), $to->toDateString()])
            ->with(['geoUnit', 'locality', 'disaggregations'])
            ->orderBy('realized_at')
            ->get();

        $rows = $activities->map(function (Activity $a): array {
            $sex = $a->disaggregations->where('phase', 'real')->where('dimension', DisaggregationDimension::Sex);
            $femmes = (int) $sex->firstWhere('key', Sex::Femme->value)?->count;
            $hommes = (int) $sex->firstWhere('key', Sex::Homme->value)?->count;

            return [
                $a->realized_at?->format('d/m/Y') ?? '—',
                $a->title,
                (string) (data_get($a, 'geoUnit.name') ?? data_get($a, 'locality.name') ?? '—'),
                $a->responsibleName(),
                $femmes + $hommes,
                $femmes,
                $hommes,
                (string) ($a->difficulties ?? '—'),
            ];
        })->all();

        return [
            'title' => 'Rapport d’activités',
            'subtitle' => $projectTitle,
            'meta' => ['Période' => $from->format('d/m/Y').' → '.$to->format('d/m/Y'), 'Activités réalisées' => (string) count($rows)],
            'headings' => ['Date', 'Activité', 'Lieu', 'Responsable', 'Participants', 'Femmes', 'Hommes', 'Difficultés'],
            'rows' => $rows,
            'filename' => 'rapport-activites-'.$projectCode,
        ];
    }

    /**
     * @return array{title: string, subtitle: string, meta: array<string, string>, headings: list<string>, rows: array<int, array<int, string|int>>, filename: string}
     */
    private static function indicators(string $frameworkId): array
    {
        $framework = ResultFramework::find($frameworkId);
        $indicators = $framework instanceof ResultFramework ? $framework->indicators()->get() : collect();

        $rows = [];
        foreach ($indicators as $indicator) {
            /** @var Indicator $indicator */
            $targets = $indicator->targets()->get()->keyBy('period_label');
            $values = $indicator->values()->get()->keyBy('period_label');
            $labels = $targets->keys()->merge($values->keys())->unique();

            foreach ($labels as $label) {
                $target = $targets->get($label)?->target_value;
                $realized = $values->get($label)?->value;
                $attainment = ($target !== null && $realized !== null)
                    ? $indicator->direction->attainment((float) $realized, (float) $target)
                    : null;

                $rows[] = [
                    trim(($indicator->code ? $indicator->code.' — ' : '').$indicator->label),
                    (string) $label,
                    $target !== null ? (string) $target : '—',
                    $realized !== null ? (string) $realized : '—',
                    $attainment !== null ? round($attainment * 100).' %' : '—',
                ];
            }
        }

        return [
            'title' => 'État des indicateurs',
            'subtitle' => (string) (ResultFramework::query()->whereKey($frameworkId)->value('name') ?? 'Cadre de résultats'),
            'meta' => ['Indicateurs' => (string) $indicators->count()],
            'headings' => ['Indicateur', 'Période', 'Cible', 'Réalisé', 'Atteinte'],
            'rows' => $rows,
            'filename' => 'etat-indicateurs-'.$frameworkId,
        ];
    }

    /**
     * @return array{title: string, subtitle: string, meta: array<string, string>, headings: list<string>, rows: array<int, array<int, string|int>>, filename: string}
     */
    private static function financial(string $projectId): array
    {
        $projectTitle = (string) (Project::query()->whereKey($projectId)->value('title') ?? 'Projet');
        $projectCode = (string) (Project::query()->whereKey($projectId)->value('code') ?? $projectId);
        $lines = BudgetLine::query()->where('project_id', $projectId)->with('category')->orderBy('label')->get();

        $rows = $lines->map(fn (BudgetLine $l): array => [
            (string) data_get($l, 'category.name', '—'),
            $l->label,
            $l->amount_gnf,
            $l->committed(),
            $l->spent(),
            $l->available(),
            $l->consumptionRate() !== null ? round($l->consumptionRate() * 100).' %' : '—',
        ])->all();

        $rows[] = [
            'TOTAL',
            '',
            (int) $lines->sum('amount_gnf'),
            (int) $lines->sum(fn (BudgetLine $l): int => $l->committed()),
            (int) $lines->sum(fn (BudgetLine $l): int => $l->spent()),
            (int) $lines->sum(fn (BudgetLine $l): int => $l->available()),
            '',
        ];

        return [
            'title' => 'Rapport financier',
            'subtitle' => $projectTitle,
            'meta' => ['Devise' => 'GNF', 'Lignes' => (string) $lines->count()],
            'headings' => ['Rubrique', 'Ligne', 'Budget', 'Engagé', 'Dépensé', 'Disponible', 'Conso'],
            'rows' => $rows,
            'filename' => 'rapport-financier-'.$projectCode,
        ];
    }
}
