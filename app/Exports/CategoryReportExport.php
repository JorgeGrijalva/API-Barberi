<?php
declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryReportExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection|array $rows)
    {
    }

    public function collection(): Collection|array
    {
        return $this->rows->map(fn(Collection|array $row) => $this->tableBody($row));
    }

    public function headings(): array
    {
        return [
            'Category',
            'Item sold',
            'Net sales',
            'Products',
            'Orders',
        ];
    }

    private function tableBody(Collection|array $row): array
    {
        return [
            'category'          => data_get($row, 'title'),
            'quantity'          => data_get($row, 'quantity'),
            'price'             => data_get($row, 'price'),
            'products_count'    => data_get($row, 'products_count'),
            'count'             => data_get($row, 'count'),
        ];
    }
}
