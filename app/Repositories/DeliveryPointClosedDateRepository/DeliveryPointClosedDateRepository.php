<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryPointClosedDateRepository;

use App\Models\DeliveryPoint;
use App\Models\DeliveryPointClosedDate;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Schema;

class DeliveryPointClosedDateRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return DeliveryPointClosedDate::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('delivery_point_closed_dates', $column) ? $column : 'id';
        }

        return DeliveryPoint::with([
            'closedDates' => fn($q) => $q->select(['id', 'date', 'delivery_point_id'])
        ])
            ->whereHas('closedDates')
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $deliveryPointId
     * @return Collection
     */
    public function show(int $deliveryPointId): Collection
    {
        return DeliveryPointClosedDate::where('delivery_point_id', $deliveryPointId)
            ->orderBy('date')
            ->get();
    }
}
