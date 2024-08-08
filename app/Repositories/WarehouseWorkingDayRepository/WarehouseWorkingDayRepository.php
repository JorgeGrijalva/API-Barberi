<?php
declare(strict_types=1);

namespace App\Repositories\WarehouseWorkingDayRepository;

use App\Models\Warehouse;
use App\Models\WarehouseWorkingDay;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WarehouseWorkingDayRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return WarehouseWorkingDay::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return Warehouse::with([
            'workingDays' => fn($q) => $q->select(['id', 'day', 'from', 'to', 'disabled', 'warehouse_id'])
        ])
            ->whereHas('workingDays')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $warehouseId
     * @return Collection
     */
    public function show(int $warehouseId): Collection
    {
        return WarehouseWorkingDay::where('warehouse_id', $warehouseId)
            ->orderBy('day')
            ->get();
    }
}
