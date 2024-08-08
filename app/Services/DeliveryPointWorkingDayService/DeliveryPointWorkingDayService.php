<?php
declare(strict_types=1);

namespace App\Services\DeliveryPointWorkingDayService;

use App\Helpers\ResponseError;
use App\Models\DeliveryPoint;
use App\Models\DeliveryPointWorkingDay;
use App\Services\CoreService;
use Throwable;

class DeliveryPointWorkingDayService extends CoreService
{
    protected function getModelClass(): string
    {
        return DeliveryPointWorkingDay::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $date['delivery_point_id'] = data_get($data, 'delivery_point_id');

                DeliveryPointWorkingDay::updateOrCreate([
                    ['delivery_point_id', data_get($data, 'delivery_point_id')],
                    ['day',     data_get($date, 'day')]
                ], $date);

            }

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => ResponseError::ERROR_501, 'code' => ResponseError::ERROR_501];
        }
    }

    public function update(int $id, array $data): array
    {
        try {

            DeliveryPoint::find($id)->workingDays()->delete();

            foreach (data_get($data, 'dates', []) as $date) {

                DeliveryPointWorkingDay::create($date + ['delivery_point_id' => $id]);

            }

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function changeDisabled(int $id): array
    {
        try {
            $model = DeliveryPointWorkingDay::find($id);

            $model->update([
                'disabled' => !$model->disabled,
            ]);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    public function delete(?array $ids = []) {

        $models = DeliveryPointWorkingDay::find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

    }
}
