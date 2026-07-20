<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Beneficiaries;

use App\Filament\App\Resources\Beneficiaries\Pages\CreateBeneficiary;
use App\Filament\App\Resources\Beneficiaries\Pages\EditBeneficiary;
use App\Filament\App\Resources\Beneficiaries\Pages\ListBeneficiaries;
use App\Filament\App\Resources\Beneficiaries\RelationManagers\ParticipationsRelationManager;
use App\Filament\App\Resources\Beneficiaries\Schemas\BeneficiaryForm;
use App\Filament\App\Resources\Beneficiaries\Tables\BeneficiariesTable;
use App\Models\Beneficiary;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BeneficiaryResource extends Resource
{
    protected static ?string $model = Beneficiary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'bénéficiaire';

    protected static ?string $pluralModelLabel = 'bénéficiaires';

    protected static string|UnitEnum|null $navigationGroup = 'Suivi-évaluation';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return BeneficiaryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BeneficiariesTable::configure($table);
    }

    /** Registre réservé à la S&E et à l'admin (nominatifs sensibles, RGSE-09). */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se']);
    }

    public static function getRelations(): array
    {
        return [
            ParticipationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBeneficiaries::route('/'),
            'create' => CreateBeneficiary::route('/create'),
            'edit' => EditBeneficiary::route('/{record}/edit'),
        ];
    }
}
