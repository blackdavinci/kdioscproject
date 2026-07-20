<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Exports\ReportExport;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ResultFramework;
use App\Models\User;
use App\Support\Reports\ReportBuilder;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Génération des rapports (Spec 08) : activités, indicateurs, financier, en
 * Excel et PDF. Consomme les modules Spec 03/05/06.
 *
 * @property string $reportType
 * @property string|null $projectId
 * @property string|null $frameworkId
 * @property string|null $periodStart
 * @property string|null $periodEnd
 */
class Reports extends Page
{
    protected string $view = 'filament.app.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Suivi-évaluation';

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $title = 'Rapports';

    protected static ?int $navigationSort = 5;

    public string $reportType = 'activities';

    public ?string $projectId = null;

    public ?string $frameworkId = null;

    public ?string $periodStart = null;

    public ?string $periodEnd = null;

    public function mount(): void
    {
        $this->projectId = $this->projectOptions()->keys()->first();
        $this->frameworkId = $this->frameworkOptions()->keys()->first();
        $this->periodStart = now()->startOfYear()->toDateString();
        $this->periodEnd = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['admin', 'chef_projet', 'responsable_se', 'responsable_financier']);
    }

    /**
     * @return array<string, string>
     */
    public function reportTypes(): array
    {
        return [
            'activities' => 'Rapport d’activités (période)',
            'indicators' => 'État des indicateurs (cadre de résultats)',
            'financial' => 'Rapport financier (projet)',
        ];
    }

    /**
     * @return Collection<string, string>
     */
    public function projectOptions(): Collection
    {
        return Project::query()->orderBy('title')->pluck('title', 'id');
    }

    /**
     * @return Collection<string, string>
     */
    public function frameworkOptions(): Collection
    {
        return ResultFramework::query()->orderBy('name')->pluck('name', 'id');
    }

    public function excel(): ?BinaryFileResponse
    {
        $report = ReportBuilder::build($this->reportType, $this->params());

        return Excel::download(
            new ReportExport($report['headings'], $report['rows'], $report['title']),
            $report['filename'].'.xlsx',
        );
    }

    public function pdf(): StreamedResponse
    {
        $report = ReportBuilder::build($this->reportType, $this->params());
        $tenant = Filament::getTenant();

        $pdf = Pdf::loadView('reports.table', [
            'organization' => $tenant instanceof Organization ? $tenant->name : '',
            'title' => $report['title'],
            'subtitle' => $report['subtitle'],
            'meta' => $report['meta'],
            'headings' => $report['headings'],
            'rows' => $report['rows'],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $report['filename'].'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function params(): array
    {
        return [
            'project_id' => $this->projectId,
            'framework_id' => $this->frameworkId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
        ];
    }
}
