<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Pages;

use App\Filament\App\Resources\Projects\ProjectResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        $user = Filament::auth()->user();
        $canCreate = $user instanceof User && $user->hasAnyRole(['admin', 'chef_projet']);

        return $canCreate ? [CreateAction::make()->label('Créer un projet')] : [];
    }
}
