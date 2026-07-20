<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries\Pages;

use App\Filament\App\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Tenancy\TenantContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un bénéficiaire')];
    }

    /** Comptage unique vs participations (RGSE-11), sans aucun nominatif. */
    public function getSubheading(): ?string
    {
        $uniques = Beneficiary::count();
        $participations = DB::table('beneficiary_activity')
            ->join('beneficiaries', 'beneficiaries.id', '=', 'beneficiary_activity.beneficiary_id')
            ->when(app(TenantContext::class)->id(), fn ($q, $id) => $q->where('beneficiaries.organization_id', $id))
            ->count();

        return "{$uniques} bénéficiaire(s) unique(s) · {$participations} participation(s)";
    }
}
