<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Subscriptions\Pages;

use App\Filament\Admin\Resources\Billing\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    // Les abonnements sont créés à la création de l'organisation, pas à la main.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
