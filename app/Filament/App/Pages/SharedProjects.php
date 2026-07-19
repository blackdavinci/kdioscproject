<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vue bailleur (RGP-15/16) : accès en lecture seule aux seuls projets partagés
 * avec le compte bailleur. Périmètre strict — identité, période, statut,
 * financement synthétique et cadre logique ; jamais de données internes.
 */
class SharedProjects extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.shared-projects';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Projets partagés';

    protected static ?string $title = 'Projets partagés avec vous';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('bailleur');
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $userId = $user instanceof User ? $user->id : null;

        return $table
            ->query(fn (): Builder => Project::query()
                ->whereHas('shares', fn (Builder $q): Builder => $q->whereNull('revoked_at')->where('user_id', $userId))
                ->withSum('donors', 'amount_gnf'))
            ->emptyStateHeading('Aucun projet partagé avec vous')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('title')->label('Titre')->searchable()->wrap(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('start_date')->label('Début')->date('d/m/Y'),
                TextColumn::make('end_date')->label('Fin')->date('d/m/Y'),
                TextColumn::make('donors_sum_amount_gnf')
                    ->label('Financement (GNF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->placeholder('0'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Voir')
                    ->schema(fn (Schema $schema): Schema => self::detailSchema($schema)),
            ]);
    }

    /** Périmètre strict de la vue bailleur (RGP-16). */
    protected static function detailSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Titre'),
                    TextEntry::make('code')->label('Code'),
                    TextEntry::make('status')->label('Statut')->badge(),
                    TextEntry::make('description')->label('Description')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Période et financement')
                ->columns(2)
                ->schema([
                    TextEntry::make('start_date')->label('Début')->date('d/m/Y'),
                    TextEntry::make('end_date')->label('Fin')->date('d/m/Y'),
                    TextEntry::make('donors_sum_amount_gnf')
                        ->label('Financement total (GNF)')
                        ->numeric(thousandsSeparator: ' ')
                        ->placeholder('0'),
                ]),
            Section::make('Cadre logique')
                ->schema([
                    TextEntry::make('logframe')
                        ->hiddenLabel()
                        ->state(fn (Project $record): string => self::logframeSummary($record))
                        ->placeholder('Aucun cadre logique renseigné.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected static function logframeSummary(Project $project): string
    {
        return $project->logframeNodes()
            ->orderBy('position')
            ->get()
            ->map(fn ($node): string => trim(($node->code ? $node->code.' — ' : '').$node->title))
            ->implode("\n");
    }
}
