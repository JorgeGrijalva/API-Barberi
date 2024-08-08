<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryManSettingRepository;

use App\Models\DeliveryManSetting;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryManSettingRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return DeliveryManSetting::class;
    }

    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var DeliveryManSetting $deliveryManSetting */
        $deliveryManSetting = $this->model();

        return $deliveryManSetting
            ->filter($filter)
            ->with('deliveryman:id,uuid,active,firstname,lastname,phone,img')
            ->paginate($filter['perPage'] ?? 10);
    }

    public function detail(?int $id = null, ?int $userId = null): ?DeliveryManSetting
    {
        return $this->model()
            ->with(array_merge(['deliveryman', 'galleries'], $this->getWith()))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($id, fn($q) => $q->where('id', $id))
            ->first();
    }

}
