<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks\Pages;

use App\Enums\TaskStatus;
use App\Filament\App\Resources\Tasks\TaskResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['created_by'] = $user instanceof User ? $user->id : null;

        if (($data['status'] ?? null) === TaskStatus::Termine->value) {
            $data['completed_at'] = now();
        }

        return $data;
    }
}
