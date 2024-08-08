<?php
declare(strict_types=1);

namespace App\Services\WarehouseWorkingDayService;

use App\Helpers\ResponseError;
use App\Models\Warehouse;
use App\Models\WarehouseWorkingDay;
use App\Services\CoreService;
use Throwable;

class WarehouseWorkingDayService extends CoreService
{
    protected function getModelClass(): string
    {
        return WarehouseWorkingDay::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $date['warehouse_id'] = data_get($data, 'warehouse_id');

                WarehouseWorkingDay::updateOrCreate([
                    ['warehouse_id', data_get($data, 'warehouse_id')],
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

            Warehouse::find($id)->workingDays()->delete();

            foreach (data_get($data, 'dates', []) as $date) {

                WarehouseWorkingDay::create($date + ['warehouse_id' => $id]);

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
            $model = WarehouseWorkingDay::find($id);

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

        $models = WarehouseWorkingDay::find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

    }
}
