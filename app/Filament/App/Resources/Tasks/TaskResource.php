<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks;

use App\Filament\App\RelationManagers\CommentsRelationManager;
use App\Filament\App\Resources\Tasks\Pages\CreateTask;
use App\Filament\App\Resources\Tasks\Pages\EditTask;
use App\Filament\App\Resources\Tasks\Pages\ListTasks;
use App\Filament\App\Resources\Tasks\Schemas\TaskForm;
use App\Filament\App\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $modelLabel = 'tâche';

    protected static ?string $pluralModelLabel = 'tâches';

    protected static string|UnitEnum|null $navigationGroup = 'Collaboration';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    /**
     * Visibilité (RGT-05) : admin et S&E voient tout ; les autres voient les tâches
     * qui leur sont assignées, qu'ils ont créées, ou des projets de leur équipe.
     *
     * @return Builder<Task>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Task> $query */
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['admin', 'responsable_se'])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user): void {
            $q->where('assignee_user_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('project.members', fn (Builder $m): Builder => $m->where('user_id', $user->id));
        });
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ! $user->hasRole('bailleur');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
