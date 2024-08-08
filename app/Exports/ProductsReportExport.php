<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection,
    ShouldAutoSize,
    WithBatchInserts,
    WithChunkReading,
    WithHeadings,
    WithMapping,
};

class ProductsReportExport implements FromCollection, WithMapping, ShouldAutoSize, WithBatchInserts, WithChunkReading, WithHeadings
{
    private Collection $rows;

    /**
     * Export constructor.
     *
     * @param Collection $rows
     */
    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    public function map($row): array
    {
        /** @var Product $row */
        return [
            $row->active,
        ];
    }

    public function headings(): array
    {
        return [
            'Product title',
            'Items sold',
            'Net sales',
            'Order',
            'Category',
            'Variations',
            'Status',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
