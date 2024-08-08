<?php
declare(strict_types=1);

namespace App\Services\DeliveryPointClosedDateService;

use App\Helpers\ResponseError;
use App\Models\DeliveryPoint;
use App\Models\DeliveryPointClosedDate;
use App\Services\CoreService;
use Throwable;

class DeliveryPointClosedDateService extends CoreService
{
    protected function getModelClass(): string
    {
        return DeliveryPointClosedDate::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $exist = DeliveryPointClosedDate::where([
                    ['delivery_point_id', data_get($data, 'delivery_point_id')],
                    ['date', $date]
                ])->exists();

                if ($exist) {
                    continue;
                }

                $this->model()->create(['delivery_point_id' => data_get($data, 'delivery_point_id'), 'date' => $date]);
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

            DeliveryPoint::find($id)->closedDates()->delete();

            $dates = data_get($data, 'dates');

            foreach (is_array($dates) ? $dates : []  as $date) {

                DeliveryPointClosedDate::create(['delivery_point_id' => $id, 'date' => $date]);

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

        $deliveryPointClosedDates = DeliveryPointClosedDate::find(is_array($ids) ? $ids : []);

        foreach ($deliveryPointClosedDates as $deliveryPointClosedDate) {
            $deliveryPointClosedDate->delete();
        }

    }
}
