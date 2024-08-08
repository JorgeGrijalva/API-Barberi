<?php
declare(strict_types=1);

namespace App\Repositories\WarehouseClosedDateRepository;

use App\Models\Warehouse;
use App\Models\WarehouseClosedDate;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WarehouseClosedDateRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return WarehouseClosedDate::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return Warehouse::with([
            'closedDates' => fn($q) => $q->select(['id', 'date', 'warehouse_id'])
        ])
            ->whereHas('closedDates')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $warehouseId
     * @return Collection
     */
    public function show(int $warehouseId): Collection
    {
        return WarehouseClosedDate::where('warehouse_id', $warehouseId)
            ->orderBy('date')
            ->get();
    }
}
