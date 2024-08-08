<?php
declare(strict_types=1);

namespace App\Services\DeliveryPointService;

use App\Helpers\ResponseError;
use App\Models\DeliveryPoint;
use App\Services\CoreService;
use Throwable;
use App\Traits\SetTranslations;

class DeliveryPointService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return DeliveryPoint::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DeliveryPoint::create($data);

            if (data_get($data, 'images.0')) {

                $model->update([
                    'img' => data_get($data, 'images.0')
                ]);

                $model->uploads(data_get($data, 'images'));

            }

            $this->setTranslations($model, $data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $model,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language),
            ];
        }
    }

    public function update(DeliveryPoint $deliveryPoint, array $data): array
    {
        try {
            $data['city_id'] = data_get($data, 'city_id');
            $data['area_id'] = data_get($data, 'area_id');

            $deliveryPoint->update($data);

            if (data_get($data, 'images.0')) {

                $deliveryPoint->galleries()->delete();

                $deliveryPoint->update([
                    'img' => data_get($data, 'images.0')
                ]);

                $deliveryPoint->uploads(data_get($data, 'images'));

            }

            $this->setTranslations($deliveryPoint, $data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $deliveryPoint,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function changeActive(int $id): array
    {
        try {
            $model = DeliveryPoint::find($id);

            $model->update([
                'active' => !$model->active,
            ]);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function delete(?array $ids = []) {

        $models = DeliveryPoint::find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->workingDays()->delete();
            $model->closedDates()->delete();
            $model->galleries()->delete();
            $model->delete();
        }

    }
}
