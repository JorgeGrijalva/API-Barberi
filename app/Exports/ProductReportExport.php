<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductReportExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(protected mixed $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn(OrderDetail $orderDetail) => $this->tableBody($orderDetail));
    }

    public function headings(): array
    {
        return [
            'Product title',
            'Item sold',
            'Net sales',
            'Orders',
            'Category',
            'Status',
        ];
    }

    private function tableBody(OrderDetail $orderDetail): array
    {
        $product = $orderDetail->stock?->product;

        $extras  = collect($orderDetail->stock?->stockExtras)
            ->pluck('value.value')
            ->toArray();

        $extras = implode(', ', $extras);

        $title = $product?->translation?->title;

        if (!empty($extras)) {
            $title .= " $extras";
        }

        return [
            'title'    => $title,
            'quantity' => $orderDetail->quantity ?? 0,
            'sum'      => $orderDetail->total_price ?? 0,
            'orders'   => $orderDetail->count ?? 0,
            'category' => $product?->category?->translation?->title,
            'status'   => $product?->active ? 'active' : 'inactive',
        ];
    }
}
