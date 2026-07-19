<?php

namespace App\Filament\App\Resources\TeamMembers;

use App\Filament\App\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\App\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\App\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\App\Resources\TeamMembers\Schemas\TeamMemberForm;
use App\Filament\App\Resources\TeamMembers\Tables\TeamMembersTable;
use App\Models\TeamMember;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'membre d’équipe';

    protected static ?string $pluralModelLabel = 'membres d’équipe';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation';

    protected static ?int $navigationSort = 2;

    /**
     * Gérable par l'admin et les chefs de projet (matrice §6).
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'chef_projet']);
    }

    public static function form(Schema $schema): Schema
    {
        return TeamMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
