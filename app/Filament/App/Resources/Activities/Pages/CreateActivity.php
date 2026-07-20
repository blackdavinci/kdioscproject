<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Pages;

use App\Filament\App\Resources\Activities\ActivityResource;
use App\Filament\App\Resources\Activities\Support\HandlesActivityForm;
use App\Models\Activity;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateActivity extends CreateRecord
{
    use HandlesActivityForm;

    protected static string $resource = ActivityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->stripDisaggregation($data);
        $this->assertCoherenceIfEnforced();

        $user = Filament::auth()->user();
        $data['created_by'] = $user instanceof User ? $user->id : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof Activity) {
            $this->persistDisaggregation($this->record);
        }
    }
}
