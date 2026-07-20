<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Pages;

use App\Enums\DisaggregationPhase;
use App\Filament\App\Resources\Activities\ActivityResource;
use App\Filament\App\Resources\Activities\Support\ActivityDisaggregation;
use App\Filament\App\Resources\Activities\Support\HandlesActivityForm;
use App\Models\Activity;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    use HandlesActivityForm;

    protected static string $resource = ActivityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record instanceof Activity) {
            $data['disagg'] = [
                DisaggregationPhase::Planned->value => ActivityDisaggregation::load($this->record, DisaggregationPhase::Planned),
                DisaggregationPhase::Real->value => ActivityDisaggregation::load($this->record, DisaggregationPhase::Real),
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->stripDisaggregation($data);
        $this->assertCoherenceIfEnforced();

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record instanceof Activity) {
            $this->persistDisaggregation($this->record);
        }
    }
}
