<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Pages;

use App\Filament\App\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['created_by'] = $user instanceof User ? $user->id : null;

        return $data;
    }

    /**
     * L'auteur devient chef de projet de l'équipe (RGP-13 : au moins un chef).
     */
    protected function afterCreate(): void
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $this->record instanceof Project) {
            return;
        }

        $role = ProjectRole::query()->where('name', 'Chef de projet')->first();

        $this->record->members()->create([
            'user_id' => $user->id,
            'project_role_id' => $role?->id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
