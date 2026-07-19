<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects;

use App\Filament\App\Resources\Projects\Pages\CreateProject;
use App\Filament\App\Resources\Projects\Pages\EditProject;
use App\Filament\App\Resources\Projects\Pages\ListProjects;
use App\Filament\App\Resources\Projects\Schemas\ProjectForm;
use App\Filament\App\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'projet';

    protected static ?string $pluralModelLabel = 'projets';

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    /**
     * Visibilité intra-organisation (RGP-14) : admin et responsable S&E voient tout ;
     * les autres rôles voient les projets où ils sont membres de l'équipe. Le rôle
     * bailleur passe par la vue de partage dédiée (RGP-16), pas par cette ressource.
     *
     * @return Builder<Project>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Project> $query */
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['admin', 'responsable_se'])) {
            return $query;
        }

        return $query->whereHas('members', fn (Builder $q): Builder => $q->where('user_id', $user->id));
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ! $user->hasRole('bailleur');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LogframeRelationManager::class,
            RelationManagers\MembersRelationManager::class,
            RelationManagers\ZonesRelationManager::class,
            RelationManagers\SharesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
