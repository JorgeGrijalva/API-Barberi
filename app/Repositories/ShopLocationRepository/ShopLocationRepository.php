<?php
declare(strict_types=1);

namespace App\Repositories\ShopLocationRepository;

use App\Models\ShopLocation;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Schema;

class ShopLocationRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return ShopLocation::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_locations', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with($this->getWith())
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ShopLocation $shopLocation
     * @return ShopLocation
     */
    public function show(ShopLocation $shopLocation): ShopLocation
    {
        return $shopLocation->load($this->getWith());
    }
}
