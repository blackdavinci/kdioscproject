<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Filament\App\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * Notifie l'assigné d'une tâche (RGD-07) : in-app (cloche) + e-mail.
 */
class NotifyTaskAssignment
{
    public static function notify(Task $task): void
    {
        if ($task->assignee_user_id === null) {
            return;
        }

        $user = User::find($task->assignee_user_id);
        if (! $user instanceof User) {
            return;
        }

        $url = self::url($task);

        FilamentNotification::make()
            ->title('Une tâche vous a été assignée')
            ->body('« '.$task->title.' »')
            ->sendToDatabase($user);

        if (filled($user->email)) {
            $user->notify(new TaskMailNotice('Tâche assignée', 'La tâche « '.$task->title.' » vous a été assignée.', $url));
        }
    }

    private static function url(Task $task): ?string
    {
        try {
            return TaskResource::getUrl('edit', ['record' => $task]);
        } catch (\Throwable) {
            return null;
        }
    }
}
