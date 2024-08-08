<?php
declare(strict_types=1);

namespace App\Repositories\ShopPaymentRepository;

use App\Models\Payment;
use App\Models\ShopPayment;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Schema;

class ShopPaymentRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopPayment::class;
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function list(array $filter): mixed
    {
        /** @var ShopPayment $shopPayment */
        $shopPayment = $this->model();

        return $shopPayment
            ->filter($filter)
            ->with('payment')
            ->get();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var ShopPayment $shopPayment */
        $shopPayment = $this->model();

        return $shopPayment->filter($filter)
            ->with('payment')
            ->paginate(data_get($filter, 'perPage'));
    }

    /**
     * @param int $shopId
     * @return Collection
     */
    public function shopNonExist(int $shopId): Collection
    {
        return Payment::where('active', 1)
            ->whereDoesntHave('shopPayment', fn ($q) => $q->where('shop_id', $shopId) )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param ShopPayment $shopPayment
     * @return ShopPayment
     */
    public function show(ShopPayment $shopPayment): ShopPayment
    {
        return $shopPayment
            ->loadMissing('payment');
    }
}
