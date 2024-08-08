<?php
declare(strict_types=1);

namespace App\Services\WarehouseService;

use App\Helpers\ResponseError;
use App\Models\Warehouse;
use App\Services\CoreService;
use Throwable;
use App\Traits\SetTranslations;

class WarehouseService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Warehouse::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = Warehouse::create($data);

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

    public function update(Warehouse $warehouse, array $data): array
    {
        try {
            $warehouse->update($data);

            if (data_get($data, 'images.0')) {

                $warehouse->galleries()->delete();

                $warehouse->update([
                    'img' => data_get($data, 'images.0')
                ]);

                $warehouse->uploads(data_get($data, 'images'));

            }

            $this->setTranslations($warehouse, $data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $warehouse,
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
            $model = Warehouse::find($id);

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

        $models = Warehouse::find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->workingDays()->delete();
            $model->closedDates()->delete();
            $model->galleries()->delete();
            $model->delete();
        }

    }
}
