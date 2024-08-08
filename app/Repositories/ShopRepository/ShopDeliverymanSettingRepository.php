<?php
declare(strict_types=1);

namespace App\Repositories\ShopRepository;

use App\Models\ShopDeliverymanSetting;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ShopDeliverymanSettingRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopDeliverymanSetting::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        /** @var ShopDeliverymanSetting $models */
        $models = $this->model();

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_deliveryman_settings', $column) ? $column : 'id';
        }

        return $models
            ->filter($filter)
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ShopDeliverymanSetting $model
     * @return ShopDeliverymanSetting|null
     */
    public function show(ShopDeliverymanSetting $model): ShopDeliverymanSetting|null
    {
        return $model;
    }
}
