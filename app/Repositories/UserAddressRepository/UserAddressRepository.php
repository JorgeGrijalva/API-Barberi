<?php
declare(strict_types=1);

namespace App\Repositories\UserAddressRepository;

use App\Models\UserAddress;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class UserAddressRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return UserAddress::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var UserAddress $model */
        $model  = $this->model();
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('user_addresses', $column) ? $column : 'id';
        }

        return $model
            ->filter($filter)
            ->with(array_merge(['user:id,firstname,lastname,img'], $this->getWith()))
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param UserAddress $model
     * @return UserAddress
     */
    public function show(UserAddress $model): UserAddress
    {
        return $model->loadMissing(array_merge(['user:id,firstname,lastname,img'], $this->getWith()));
    }

    /**
     * @param int $userId
     * @return UserAddress
     */
    public function getActive(int $userId): UserAddress
    {
        return UserAddress::where([
            'active'  => 1,
            'user_id' => $userId
        ])->first();
    }
}
