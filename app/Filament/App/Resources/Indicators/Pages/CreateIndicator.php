<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Pages;

use App\Filament\App\Resources\Indicators\IndicatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIndicator extends CreateRecord
{
    protected static string $resource = IndicatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
