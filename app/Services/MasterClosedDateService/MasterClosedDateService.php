<?php
declare(strict_types=1);

namespace App\Services\MasterClosedDateService;

use App\Helpers\ResponseError;
use App\Models\MasterClosedDate;
use App\Models\User;
use App\Services\CoreService;
use Throwable;

class MasterClosedDateService extends CoreService
{
    protected function getModelClass(): string
    {
        return MasterClosedDate::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $exist = MasterClosedDate::where([
                    ['master_id', $data['master_id']],
                    ['date',      $date]
                ])->exists();

                if ($exist) {
                    continue;
                }

                $this->model()->create(['master_id' => data_get($data, 'master_id'), 'date' => $date]);
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

            User::find($id)->closedDates()->delete();

            $dates = data_get($data, 'dates');

            foreach (is_array($dates) ? $dates : []  as $date) {

                MasterClosedDate::create(['master_id' => $id, 'date' => $date]);

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

    public function delete(?array $ids = [], array $filter = []) {

        $closedDates = MasterClosedDate::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($closedDates as $closedDate) {
            $closedDate->delete();
        }

    }
}
