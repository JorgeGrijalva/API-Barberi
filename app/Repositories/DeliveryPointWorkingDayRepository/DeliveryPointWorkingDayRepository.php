<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryPointWorkingDayRepository;

use App\Models\DeliveryPoint;
use App\Models\DeliveryPointWorkingDay;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Schema;

class DeliveryPointWorkingDayRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return DeliveryPointWorkingDay::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('delivery_point_working_days', $column) ? $column : 'id';
        }

        return DeliveryPoint::with([
            'workingDays' => fn($q) => $q->select(['id', 'day', 'from', 'to', 'disabled', 'delivery_point_id'])
        ])
            ->whereHas('workingDays')
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $deliveryPointId
     * @return Collection
     */
    public function show(int $deliveryPointId): Collection
    {
        return DeliveryPointWorkingDay::where('delivery_point_id', $deliveryPointId)
            ->orderBy('day')
            ->get();
    }
}
