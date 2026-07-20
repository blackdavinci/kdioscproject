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
 * Calendrier mensuel des activités (story 3.5) : rendu serveur, activités placées
 * à leur date prévue, navigation mois précédent/suivant, filtre par projet.
 */
class ActivityCalendar extends Page
{
    protected string $view = 'filament.app.pages.activity-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?string $navigationLabel = 'Calendrier';

    protected static ?string $title = 'Calendrier des activités';

    protected static ?int $navigationSort = 5;

    public int $year;

    public int $month;

    public ?string $projectId = null;

    public function mount(): void
    {
        $now = now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    public function previousMonth(): void
    {
        $cursor = Carbon::parse(sprintf('%04d-%02d-01', $this->year, $this->month))->subMonth();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    public function nextMonth(): void
    {
        $cursor = Carbon::parse(sprintf('%04d-%02d-01', $this->year, $this->month))->addMonth();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    public function monthLabel(): string
    {
        return Carbon::parse(sprintf('%04d-%02d-01', $this->year, $this->month))->translatedFormat('F Y');
    }

    /**
     * @return Collection<string, string>
     */
    public function projectOptions(): Collection
    {
        return Project::query()->orderBy('title')->pluck('title', 'id');
    }

    /**
     * Grille : liste de semaines, chaque semaine = 7 jours (date + activités).
     *
     * @return array<int, array<int, array{date: Carbon, inMonth: bool, activities: Collection<int, Activity>}>>
     */
    public function weeks(): array
    {
        $first = Carbon::parse(sprintf('%04d-%02d-01', $this->year, $this->month))->startOfDay();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $user = Filament::auth()->user();
        $query = Activity::query()
            ->whereBetween('planned_start', [$gridStart->toDateString(), $gridEnd->toDateString()]);

        if ($this->projectId !== null) {
            $query->where('project_id', $this->projectId);
        }

        if ($user instanceof User && ! $user->hasAnyRole(['admin', 'responsable_se'])) {
            $query->where(fn ($q) => $q->where('responsible_user_id', $user->id)
                ->orWhereHas('project.members', fn ($m) => $m->where('user_id', $user->id)));
        }

        $byDay = $query->get()->groupBy(fn (Activity $a): string => $a->planned_start->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => (int) $cursor->month === $this->month,
                    'activities' => $byDay->get($cursor->toDateString(), collect()),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
