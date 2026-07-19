<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Vue portefeuille (story 2.6) : synthèse de tous les projets de l'organisation —
 * statuts, périodes, financement total — réservée à la direction et au S&E.
 */
class Portfolio extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.portfolio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Projets';

    protected static ?string $navigationLabel = 'Portefeuille';

    protected static ?string $title = 'Portefeuille de projets';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_se']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Project::query()->withSum('donors', 'amount_gnf'))
            ->emptyStateHeading('Aucun projet')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('donors_sum_amount_gnf')
                    ->label('Financement (GNF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->placeholder('0')
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label('Équipe')
                    ->counts('members')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ProjectStatus::class),
            ]);
    }
}
