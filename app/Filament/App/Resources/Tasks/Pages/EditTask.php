<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks\Pages;

use App\Enums\TaskStatus;
use App\Filament\App\Resources\Tasks\Support\CompleteRecurringTask;
use App\Filament\App\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $wasCompleted = $this->record instanceof Task && $this->record->status === TaskStatus::Termine;
        $nowCompleted = ($data['status'] ?? null) === TaskStatus::Termine->value;

        if ($nowCompleted && ! $wasCompleted) {
            $data['completed_at'] = now();
            $this->justCompleted = true;
        } elseif (! $nowCompleted) {
            $data['completed_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // À la clôture d'une tâche récurrente, générer l'occurrence suivante (RGT-13).
        if ($this->justCompleted && $this->record instanceof Task) {
            CompleteRecurringTask::spawnNext($this->record);
        }
    }

    private bool $justCompleted = false;
}
