<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Support\DashboardScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dernières activités réalisées (RGD-03), scopées aux projets visibles.
 */
class RecentActivities extends TableWidget
{
    protected static ?string $heading = 'Dernières activités réalisées';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Activity::query()
                ->whereIn('project_id', DashboardScope::visibleProjectIds())
                ->where('status', ActivityStatus::Realisee->value)
                ->latest('realized_at'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aucune activité réalisée')
            ->columns([
                TextColumn::make('realized_at')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('title')->label('Activité')->wrap(),
                TextColumn::make('project.title')->label('Projet')->badge(),
                TextColumn::make('responsible')->label('Responsable')->state(fn (Activity $r): string => $r->responsibleName()),
            ]);
    }
}
