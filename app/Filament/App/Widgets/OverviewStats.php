<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\BudgetLine;
use App\Models\Project;
use App\Models\Task;
use App\Support\DashboardScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Synthèse du tableau de bord (RGD-02) : projets en cours, consommation
 * budgétaire, tâches en retard, alertes budgétaires — scopé au rôle.
 */
class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $projectIds = DashboardScope::visibleProjectIds();

        $enCours = Project::query()->whereIn('id', $projectIds)->where('status', ProjectStatus::EnCours->value)->count();

        $lines = BudgetLine::query()->whereIn('project_id', $projectIds)->get();
        $budget = (int) $lines->sum('amount_gnf');
        $spent = (int) $lines->sum(fn (BudgetLine $l): int => $l->spent());
        $consumption = $budget > 0 ? round($spent / $budget * 100).' %' : '—';
        $alerts = $lines->filter(fn (BudgetLine $l): bool => $l->isOverThreshold())->count();

        $overdue = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('status', '!=', TaskStatus::Termine->value)
            ->where(function ($q) use ($projectIds): void {
                $q->whereIn('project_id', $projectIds)->orWhereNull('project_id');
            })
            ->count();

        return [
            Stat::make('Projets en cours', (string) $enCours)
                ->icon('heroicon-o-rectangle-stack')
                ->color('success'),
            Stat::make('Consommation budgétaire', $consumption)
                ->description(number_format($spent, 0, ',', ' ').' / '.number_format($budget, 0, ',', ' ').' GNF')
                ->icon('heroicon-o-calculator')
                ->color($alerts > 0 ? 'warning' : 'info'),
            Stat::make('Tâches en retard', (string) $overdue)
                ->icon('heroicon-o-clock')
                ->color($overdue > 0 ? 'danger' : 'gray'),
            Stat::make('Alertes budgétaires', (string) $alerts)
                ->description('lignes au-dessus du seuil')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($alerts > 0 ? 'danger' : 'gray'),
        ];
    }
}
