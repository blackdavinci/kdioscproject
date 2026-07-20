<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Tableau kanban des tâches (RGT-06) : colonnes par statut, glisser-déposer
 * (SortableJS) qui persiste statut + position. Filtre par projet / hors projet.
 */
class TaskBoard extends Page
{
    protected string $view = 'filament.app.pages.task-board';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Collaboration';

    protected static ?string $navigationLabel = 'Tableau kanban';

    protected static ?string $title = 'Tableau kanban';

    protected static ?int $navigationSort = 1;

    public ?string $projectId = null;

    public bool $onlyInternal = false;

    /**
     * @return Collection<string, string>
     */
    public function projectOptions(): Collection
    {
        return Project::query()->orderBy('title')->pluck('title', 'id');
    }

    /**
     * @return list<TaskStatus>
     */
    public function statuses(): array
    {
        return TaskStatus::cases();
    }

    /**
     * Tâches visibles regroupées par statut.
     *
     * @return array<string, Collection<int, Task>>
     */
    public function columns(): array
    {
        $tasks = $this->visibleQuery()
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get();

        $grouped = [];
        foreach (TaskStatus::cases() as $status) {
            $grouped[$status->value] = $tasks->where('status', $status);
        }

        return $grouped;
    }

    public function moveTask(string $taskId, string $status, int $position): void
    {
        $target = TaskStatus::tryFrom($status);
        $task = $this->visibleQuery()->find($taskId);

        if (! $task instanceof Task || $target === null) {
            return;
        }

        // Une tâche d'un projet en lecture seule ne peut être déplacée (RGP-07).
        if ($task->project && $task->project->isReadOnly()) {
            Notification::make()->warning()->title('Projet en lecture seule')->send();

            return;
        }

        $task->update([
            'status' => $target,
            'position' => $position,
            'completed_at' => $target === TaskStatus::Termine ? ($task->completed_at ?? now()) : null,
        ]);
    }

    /**
     * @return Builder<Task>
     */
    private function visibleQuery(): Builder
    {
        $query = Task::query();
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->hasAnyRole(['admin', 'responsable_se'])) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('assignee_user_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('project.members', fn (Builder $m): Builder => $m->where('user_id', $user->id));
            });
        }

        if ($this->onlyInternal) {
            $query->whereNull('project_id')->whereNull('activity_id');
        } elseif ($this->projectId !== null) {
            $query->where('project_id', $this->projectId);
        }

        return $query;
    }
}
