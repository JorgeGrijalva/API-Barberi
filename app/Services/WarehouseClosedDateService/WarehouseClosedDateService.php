<?php
declare(strict_types=1);

namespace App\Services\WarehouseClosedDateService;

use App\Helpers\ResponseError;
use App\Models\Warehouse;
use App\Models\WarehouseClosedDate;
use App\Services\CoreService;
use Throwable;

class WarehouseClosedDateService extends CoreService
{
    protected function getModelClass(): string
    {
        return WarehouseClosedDate::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $exist = WarehouseClosedDate::where([
                    ['warehouse_id', data_get($data, 'warehouse_id')],
                    ['date', $date]
                ])->exists();

                if ($exist) {
                    continue;
                }

                $this->model()->create(['warehouse_id' => data_get($data, 'warehouse_id'), 'date' => $date]);
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

            Warehouse::find($id)->closedDates()->delete();

            $dates = data_get($data, 'dates');

            foreach (is_array($dates) ? $dates : []  as $date) {

                WarehouseClosedDate::create(['warehouse_id' => $id, 'date' => $date]);

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

    public function delete(?array $ids = []) {

        $warehouseClosedDates = WarehouseClosedDate::find(is_array($ids) ? $ids : []);

        foreach ($warehouseClosedDates as $warehouseClosedDate) {
            $warehouseClosedDate->delete();
        }

    }
}
