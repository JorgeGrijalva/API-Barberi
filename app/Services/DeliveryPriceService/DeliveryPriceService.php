<?php
declare(strict_types=1);

namespace App\Services\DeliveryPriceService;

use App\Helpers\ResponseError;
use App\Models\DeliveryPrice;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;

final class DeliveryPriceService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return DeliveryPrice::class;
    }

    public function create(array $data): array
    {
        try {
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(DeliveryPrice $model, $data): array
    {
        try {
            $data['city_id'] = data_get($data, 'city_id');
            $data['area_id'] = data_get($data, 'area_id');

            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $models = DeliveryPrice::whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($models as $model) {
            /** @var DeliveryPrice $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
