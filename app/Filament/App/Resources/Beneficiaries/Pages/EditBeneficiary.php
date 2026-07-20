<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\Pages;

use App\Filament\App\Resources\Beneficiaries\BeneficiaryResource;
use App\Support\BeneficiaryFingerprint;
use App\Tenancy\TenantContext;
use Filament\Resources\Pages\EditRecord;

class EditBeneficiary extends EditRecord
{
    protected static string $resource = BeneficiaryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $orgId = (string) app(TenantContext::class)->id();
        $data['name_fingerprint'] = BeneficiaryFingerprint::make($orgId, $data['full_name'] ?? null);

        return $data;
    }
}
