<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\Pages;

use App\Filament\App\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Support\BeneficiaryFingerprint;
use App\Tenancy\TenantContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBeneficiary extends CreateRecord
{
    protected static string $resource = BeneficiaryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $orgId = (string) app(TenantContext::class)->id();
        $fingerprint = BeneficiaryFingerprint::make($orgId, $data['full_name'] ?? null);
        $data['name_fingerprint'] = $fingerprint;

        // Détection de doublon probable (RGSE-10) — signalement non bloquant.
        if ($fingerprint !== null && Beneficiary::where('name_fingerprint', $fingerprint)->exists()) {
            Notification::make()
                ->warning()
                ->title('Doublon probable')
                ->body('Un bénéficiaire au nom similaire existe déjà. Vérifiez avant de poursuivre.')
                ->persistent()
                ->send();
        }

        return $data;
    }
}
