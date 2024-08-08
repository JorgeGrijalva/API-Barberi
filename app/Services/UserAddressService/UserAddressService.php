<?php
declare(strict_types=1);

namespace App\Services\UserAddressService;

use App\Helpers\ResponseError;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\CoreService;
use Throwable;

class UserAddressService extends CoreService
{
    protected function getModelClass(): string
    {
        return UserAddress::class;
    }

    /**
     * Create a new Shop model.
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            /** @var UserAddress $model */
            $model = $this->model()->create($data);

            $result = User::with([
                'wallet',
                'addresses' => fn($q) => $q->orderBy('id', 'desc')
            ])
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'email',
                    'phone',
                    'img',
                    'birthday',
                    'gender',
                    'active',
                    'img',
                    'my_referral',
                    'r_count',
                    'r_avg',
                    'r_sum',
                    'o_count',
                    'o_sum',
                ])
                ->where('id', $model->user_id)
                ->first();

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $result
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage() . ' ' . $e->getLine()
            ];
        }
    }

    /**
     * Update specified Shop model.
     * @param UserAddress $model
     * @param array $data
     * @return array
     */
    public function update(UserAddress $model, array $data): array
    {
        try {
            $data['city_id'] = data_get($data, 'city_id');
            $data['area_id'] = data_get($data, 'area_id');

            $model->update($data);

            $result = User::with([
                'wallet',
                'addresses'
            ])
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'email',
                    'phone',
                    'img',
                    'birthday',
                    'gender',
                    'active',
                    'img',
                    'my_referral',
                    'r_count',
                    'r_avg',
                    'r_sum',
                    'o_count',
                    'o_sum',
                ])
                ->where('id', $model->user_id)
                ->first();
            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $result,
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

    /**
     * Update specified Shop model.
     * @param int $id
     * @param int|null $userId
     * @return array
     */
    public function setActive(int $id, ?int $userId): array
    {
        try {
            $model = UserAddress::with([
                'user.addresses' => fn($q) => $q->where('active', 1)
            ])
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->find($id);

            if (empty($model)) {
                return [
                    'status' => false,
                    'code'   => ResponseError::ERROR_404,
                ];
            }

            /** @var UserAddress $model */
            $addresses = $model->user?->addresses ?? [];

            foreach ($addresses as $address) {
                $address->update([
                    'active' => false
                ]);
            }

            $model->update([
                'active' => true
            ]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
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

    /**
     * Delete model.
     * @param array|null $ids
     * @param int|null $userId
     * @return array
     */
    public function delete(?array $ids = [], ?int $userId = null): array
    {
        $when = [];

        if (!empty($userId)) {
            $when = ['column' => 'user_id', 'value' => $userId];
        }

        return $this->remove($ids, when: $when);
    }

}
