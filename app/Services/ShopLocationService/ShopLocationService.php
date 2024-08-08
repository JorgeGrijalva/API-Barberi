<?php
declare(strict_types=1);

namespace App\Services\ShopLocationService;

use App\Helpers\ResponseError;
use App\Models\ShopLocation;
use App\Services\CoreService;
use Throwable;

class ShopLocationService extends CoreService
{
    protected function getModelClass(): string
    {
        return ShopLocation::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $result = $this->model()->updateOrCreate($data);

            return [
                'status'    => true,
                'message'   => ResponseError::NO_ERROR,
                'data'      => $result
            ];
        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage()
            ];
        }
    }

    /**
     * @param ShopLocation $shopLocation
     * @param array $data
     * @return array
     */
    public function update(ShopLocation $shopLocation, array $data): array
    {
        try {
            $shopLocation->update($data);

            return [
                'status'    => true,
                'message'   => ResponseError::NO_ERROR,
                'data'      => $shopLocation
            ];
        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_502,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $models = $this->model()->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->get();

        foreach ($models as $model) {
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }
}
