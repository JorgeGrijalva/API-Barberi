<?php
declare(strict_types=1);

namespace App\Services\ShopSocialService;

use App\Helpers\ResponseError;
use App\Models\ShopSocial;
use App\Services\CoreService;
use Exception;

class ShopSocialService extends CoreService
{
    protected function getModelClass(): string
    {
        return ShopSocial::class;
    }

    public function create(array $data): array
    {
        try {

            if (!empty(data_get($data, 'data.*'))) {
                $this->model()->where('shop_id', data_get($data, 'shop_id'))->delete();
            }

            foreach (data_get($data, 'data', []) as $item) {

                $item['shop_id'] = data_get($data, 'shop_id');

                $model = $this->model()->create($item);

                if (data_get($item, 'images.0')) {
                    $model->uploads(data_get($item, 'images'));
                    $model->update(['img' => data_get($item, 'images.0')]);
                }

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR];

        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(ShopSocial $model, $data): array
    {
        try {
            $model->update($data);

            if (data_get($data, 'images.0')) {
                $model->galleries()->delete();
                $model->uploads(data_get($data, 'images'));
                $model->update(['img' => data_get($data, 'images.0')]);
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $models = ShopSocial::when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->get();

        foreach ($models as $model) {
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }
}
