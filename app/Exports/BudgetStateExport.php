<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\BudgetLine;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export Excel de l'état budgétaire d'un projet (RGB-08) : lignes, budget, engagé,
 * dépensé, disponible, taux. Aucun nominatif.
 */
class BudgetStateExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly string $projectId) {}

    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        $lines = BudgetLine::query()
            ->where('project_id', $this->projectId)
            ->with('category')
            ->orderBy('label')
            ->get();

        $rows = $lines->map(fn (BudgetLine $l): array => [
            (string) data_get($l, 'category.name', '—'),
            $l->label,
            $l->amount_gnf,
            $l->committed(),
            $l->spent(),
            $l->available(),
            $l->consumptionRate() !== null ? round($l->consumptionRate() * 100).' %' : '—',
        ])->all();

        // Ligne de total.
        $rows[] = [
            'TOTAL',
            '',
            $lines->sum('amount_gnf'),
            $lines->sum(fn (BudgetLine $l): int => $l->committed()),
            $lines->sum(fn (BudgetLine $l): int => $l->spent()),
            $lines->sum(fn (BudgetLine $l): int => $l->available()),
            '',
        ];

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Rubrique', 'Ligne', 'Budget (GNF)', 'Engagé (GNF)', 'Dépensé (GNF)', 'Disponible (GNF)', 'Consommation'];
    }

    public function title(): string
    {
        return 'État budgétaire';
    }

    public function projectTitle(): string
    {
        return Project::query()->whereKey($this->projectId)->value('title') ?? 'Projet';
    }
}
