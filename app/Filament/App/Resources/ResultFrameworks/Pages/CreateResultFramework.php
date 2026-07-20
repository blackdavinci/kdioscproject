<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ResultFrameworks\Pages;

use App\Filament\App\Resources\ResultFrameworks\ResultFrameworkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResultFramework extends CreateRecord
{
    protected static string $resource = ResultFrameworkResource::class;
}
