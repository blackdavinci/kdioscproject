<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Support;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectStatusChange;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applique un changement de statut de projet (RGP-05/06) : vérifie la transition,
 * impose un motif pour suspendu/clôturé, historise et journalise.
 */
class ProjectTransition
{
    public static function apply(Project $project, ProjectStatus $target, ?string $reason): void
    {
        $from = $project->status;

        if (! $from->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'to_status' => "Transition de « {$from->label()} » vers « {$target->label()} » non autorisée.",
            ]);
        }

        if ($target->requiresReason() && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Un motif est obligatoire pour ce changement de statut.',
            ]);
        }

        $user = Filament::auth()->user();

        DB::transaction(function () use ($project, $from, $target, $reason, $user): void {
            $project->update(['status' => $target]);

            ProjectStatusChange::create([
                'project_id' => $project->id,
                'from_status' => $from,
                'to_status' => $target,
                'reason' => $reason,
                'changed_by' => $user instanceof User ? $user->id : null,
                'created_at' => now(),
            ]);

            activity()
                ->performedOn($project)
                ->event('status_changed')
                ->withProperties(['from' => $from->value, 'to' => $target->value, 'reason' => $reason])
                ->log('Changement de statut de projet');
        });

        Notification::make()
            ->success()
            ->title('Statut mis à jour')
            ->body("Le projet est passé à « {$target->label()} ».")
            ->send();
    }
}
