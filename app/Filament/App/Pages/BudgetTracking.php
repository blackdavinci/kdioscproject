<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Exports\BudgetStateExport;
use App\Models\BudgetLine;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/**
 * Tableau de suivi budgétaire (RGB-06) : par projet, budget vs engagé vs dépensé
 * vs disponible par ligne, taux de consommation coloré, export Excel (RGB-08).
 *
 * @property string|null $projectId
 */
class BudgetTracking extends Page
{
    protected string $view = 'filament.app.pages.budget-tracking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Budget';

    protected static ?string $navigationLabel = 'Suivi budgétaire';

    protected static ?string $title = 'Suivi budgétaire';

    protected static ?int $navigationSort = 3;

    public ?string $projectId = null;

    public function mount(): void
    {
        $this->projectId = $this->projectOptions()->keys()->first();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'responsable_financier', 'chef_projet', 'responsable_se']);
    }

    /**
     * @return Collection<string, string>
     */
    public function projectOptions(): Collection
    {
        return Project::query()->orderBy('title')->pluck('title', 'id');
    }

    /**
     * @return Collection<int, BudgetLine>
     */
    public function lines(): Collection
    {
        if ($this->projectId === null) {
            return collect();
        }

        return BudgetLine::query()
            ->where('project_id', $this->projectId)
            ->with('category')
            ->orderBy('label')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        $lines = $this->lines();

        return [
            'budget' => (int) $lines->sum('amount_gnf'),
            'committed' => (int) $lines->sum(fn (BudgetLine $l): int => $l->committed()),
            'spent' => (int) $lines->sum(fn (BudgetLine $l): int => $l->spent()),
            'available' => (int) $lines->sum(fn (BudgetLine $l): int => $l->available()),
        ];
    }

    public function export(): ?BinaryFileResponse
    {
        if ($this->projectId === null) {
            return null;
        }

        $export = new BudgetStateExport($this->projectId);

        return Excel::download($export, 'etat-budgetaire-'.$this->projectId.'.xlsx');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter en Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => $this->projectId !== null)
                ->action('export'),
        ];
    }
}
