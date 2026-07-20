<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Carbon;

/**
 * Rappel d'échéance des tâches (RGT-13) : notifie l'assigné (compte) lorsque
 * l'échéance approche du nombre de jours paramétré (`reminder_days_before`).
 * Planifiée quotidiennement. S'exécute hors contexte tenant (le global scope
 * ne restreint pas sans tenant courant).
 */
class RemindDueTasks
{
    public function handle(?Carbon $today = null): int
    {
        $today = ($today ?? now())->startOfDay();

        $tasks = Task::query()
            ->whereNotNull('reminder_days_before')
            ->whereNotNull('due_date')
            ->whereNotNull('assignee_user_id')
            ->where('status', '!=', TaskStatus::Termine->value)
            ->with('assigneeUser')
            ->get()
            ->filter(fn (Task $t): bool => $t->due_date !== null
                && $t->due_date->copy()->subDays((int) $t->reminder_days_before)->isSameDay($today));

        $count = 0;
        foreach ($tasks as $task) {
            $user = $task->assigneeUser;
            if (! $user instanceof User) {
                continue;
            }

            FilamentNotification::make()
                ->title('Échéance de tâche à venir')
                ->body('« '.$task->title.' » arrive à échéance le '.$task->due_date?->format('d/m/Y').'.')
                ->sendToDatabase($user);

            if (filled($user->email)) {
                $user->notify(new TaskMailNotice(
                    'Rappel d’échéance',
                    'La tâche « '.$task->title.' » arrive à échéance le '.$task->due_date?->format('d/m/Y').'.',
                ));
            }

            $count++;
        }

        return $count;
    }
}
