<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Chronogramme (Gantt) des activités d'un projet (story 2.5) : rendu serveur,
 * chaque activité positionnée sur l'axe temps selon ses dates planifiées.
 *
 * @property string|null $projectId
 */
class ProjectGantt extends Page
{
    protected string $view = 'filament.app.pages.project-gantt';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?string $navigationLabel = 'Chronogramme';

    protected static ?string $title = 'Chronogramme des activités';

    protected static ?int $navigationSort = 4;

    public ?string $projectId = null;

    public function mount(): void
    {
        $this->projectId = $this->projectOptions()->keys()->first();
    }

    /**
     * @return Collection<string, string>
     */
    public function projectOptions(): Collection
    {
        $user = Filament::auth()->user();
        $query = Project::query()->orderBy('title');

        if ($user instanceof User && ! $user->hasAnyRole(['admin', 'responsable_se'])) {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query->pluck('title', 'id');
    }

    /**
     * Barres du Gantt : position et largeur en pourcentage de la période projet.
     *
     * @return array{start: Carbon, end: Carbon, bars: array<int, array<string, mixed>>}|null
     */
    public function gantt(): ?array
    {
        if ($this->projectId === null) {
            return null;
        }

        $project = Project::find($this->projectId);
        if (! $project instanceof Project) {
            return null;
        }

        $activities = Activity::query()
            ->where('project_id', $project->id)
            ->orderBy('planned_start')
            ->get();

        $start = $project->start_date->copy()->startOfDay();
        $end = $project->end_date->copy()->endOfDay();
        $span = max(1, $start->diffInDays($end));

        $bars = $activities->map(function (Activity $a) use ($start, $span): array {
            $aStart = $a->planned_start->copy();
            $aEnd = ($a->planned_end ?? $a->planned_start)->copy();
            $offset = max(0, min(100, ($start->diffInDays($aStart, false) / $span) * 100));
            $width = max(2, min(100 - $offset, (max(1, $aStart->diffInDays($aEnd)) / $span) * 100));

            return [
                'title' => $a->title,
                'status' => $a->status,
                'offset' => round($offset, 2),
                'width' => round($width, 2),
                'start' => $a->planned_start->format('d/m/Y'),
            ];
        })->all();

        return ['start' => $start, 'end' => $end, 'bars' => $bars];
    }
}
