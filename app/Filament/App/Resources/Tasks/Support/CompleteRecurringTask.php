<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks\Support;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Str;

/**
 * À la clôture d'une tâche récurrente (RGT-13), génère l'occurrence suivante :
 * mêmes attributs, échéance décalée selon la fréquence, reliée par recurrence_group_id.
 */
class CompleteRecurringTask
{
    public static function spawnNext(Task $task): ?Task
    {
        if (! $task->recurrence->isRecurring()) {
            return null;
        }

        $groupId = $task->recurrence_group_id ?? (string) Str::ulid();
        if ($task->recurrence_group_id === null) {
            $task->update(['recurrence_group_id' => $groupId]);
        }

        $base = $task->due_date ?? now();

        return Task::create([
            'organization_id' => $task->organization_id,
            'project_id' => $task->project_id,
            'activity_id' => $task->activity_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_user_id' => $task->assignee_user_id,
            'assignee_team_member_id' => $task->assignee_team_member_id,
            'due_date' => $task->recurrence->next($base)->toDateString(),
            'priority' => $task->priority,
            'status' => TaskStatus::AFaire,
            'recurrence' => $task->recurrence,
            'reminder_days_before' => $task->reminder_days_before,
            'recurrence_group_id' => $groupId,
            'created_by' => $task->created_by,
        ]);
    }
}
