<?php
declare(strict_types=1);

namespace App\Services\UserWorkingDayService;

use App\Helpers\ResponseError;
use App\Models\User;
use App\Models\UserWorkingDay;
use App\Services\CoreService;
use Throwable;

class UserWorkingDayService extends CoreService
{
    protected function getModelClass(): string
    {
        return UserWorkingDay::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $date['user_id'] = $data['user_id'];

                UserWorkingDay::updateOrCreate([
                    ['user_id', $date['user_id']],
                    ['day',     $date['day']]
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

    public function update(int $userId, array $data): array
    {
        try {

            User::find($userId)->workingDays()->delete();

            foreach (data_get($data, 'dates', []) as $date) {

                UserWorkingDay::create($date + ['user_id' => $userId]);

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

    public function delete(?array $ids = [], ?int $shopId = null, ?int $userId = null) {

        $userWorkingDays = UserWorkingDay::when($userId, fn($q, $userId) => $q->where('user_id', $userId))->find((array)$ids);

        foreach ($userWorkingDays as $userWorkingDay) {
            $userWorkingDay->delete();
        }

    }
}
