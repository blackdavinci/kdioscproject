<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Pages;

use App\Filament\App\Resources\Indicators\IndicatorResource;
use Filament\Resources\Pages\EditRecord;

class EditIndicator extends EditRecord
{
    protected static string $resource = IndicatorResource::class;
}
