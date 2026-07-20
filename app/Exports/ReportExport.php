<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\Reports\ReportBuilder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export Excel générique d'un rapport (Spec 08), à partir des données préparées
 * par {@see ReportBuilder}.
 */
class ReportExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  array<int, array<int, string|int>>  $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title,
    ) {}

    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}
