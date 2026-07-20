<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Récapitulatif hebdomadaire des tâches en retard (RGT-14) : pour chaque projet
 * ayant des tâches en retard, notifie ses membres disposant d'un compte (dont le
 * chef de projet). Les tâches internes en retard notifient leur assigné.
 * Planifiée hebdomadairement. S'exécute hors contexte tenant.
 */
class SendOverdueTasksDigest
{
    public function handle(?Carbon $today = null): int
    {
        $today = ($today ?? now())->startOfDay();

        $overdue = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->where('status', '!=', TaskStatus::Termine->value)
            ->get();

        $notified = 0;

        foreach ($overdue->groupBy('project_id') as $tasks) {
            $projectId = $tasks->first()?->project_id;

            $recipients = $projectId === null
                ? $this->internalRecipients($tasks)
                : $this->projectRecipients($projectId);

            $count = $tasks->count();
            $where = $projectId === null
                ? 'vos tâches internes'
                : 'le projet « '.(Project::query()->whereKey($projectId)->value('title') ?? '—').' »';

            foreach ($recipients as $user) {
                FilamentNotification::make()
                    ->title('Tâches en retard')
                    ->body($count.' tâche(s) en retard sur '.$where.'.')
                    ->sendToDatabase($user);

                if (filled($user->email)) {
                    $user->notify(new TaskMailNotice(
                        'Tâches en retard',
                        $count.' tâche(s) en retard sur '.$where.'.',
                    ));
                }

                $notified++;
            }
        }

        return $notified;
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, User>
     */
    private function internalRecipients($tasks)
    {
        return $tasks->pluck('assignee_user_id')
            ->filter()
            ->unique()
            ->map(fn ($id) => User::find($id))
            ->filter(fn ($u): bool => $u instanceof User)
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function projectRecipients(string $projectId)
    {
        $project = Project::find($projectId);
        if (! $project instanceof Project) {
            return collect();
        }

        return $project->members()
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->map(fn ($m) => $m->user)
            ->filter(fn ($u): bool => $u instanceof User)
            ->unique('id')
            ->values();
    }
}
