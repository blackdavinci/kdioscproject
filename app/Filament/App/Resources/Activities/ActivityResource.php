<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities;

use App\Filament\App\Resources\Activities\Pages\CreateActivity;
use App\Filament\App\Resources\Activities\Pages\EditActivity;
use App\Filament\App\Resources\Activities\Pages\ListActivities;
use App\Filament\App\Resources\Activities\Schemas\ActivityForm;
use App\Filament\App\Resources\Activities\Tables\ActivitiesTable;
use App\Models\Activity;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'activité';

    protected static ?string $pluralModelLabel = 'activités';

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    /**
     * Visibilité alignée sur le périmètre projet (RGP-14) : admin et S&E voient
     * tout ; les autres voient les activités des projets de leur équipe ou dont
     * ils sont responsables ; le bailleur n'accède pas à la ressource.
     *
     * @return Builder<Activity>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Activity> $query */
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['admin', 'responsable_se'])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user): void {
            $q->where('responsible_user_id', $user->id)
                ->orWhereHas('project.members', fn (Builder $m): Builder => $m->where('user_id', $user->id));
        });
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ! $user->hasRole('bailleur');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'edit' => EditActivity::route('/{record}/edit'),
        ];
    }
}
