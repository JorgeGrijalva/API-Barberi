<?php
declare(strict_types=1);

namespace App\Repositories\ParcelOrderRepository;

use App\Models\ParcelOrder;
use App\Repositories\CoreRepository;
use App\Traits\SetCurrency;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Schema;

class ParcelOrderRepository extends CoreRepository
{
    use SetCurrency;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOrder::class;
    }

    /**
     * This is only for users route
     * @param array $filter
     * @return Paginator
     */
    public function paginate(array $filter = []): Paginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('parcel_orders', $column) ? $column : 'id';
        }

        /** @var ParcelOrder $parcelOrder */
        $parcelOrder = $this->model();

        return $parcelOrder
            ->filter($filter)
            ->with([
                'user:id,lastname,firstname,img,email,phone',
                'deliveryman:id,lastname,firstname',
                'transaction',
                'transaction.paymentSystem:id,tag',
                'currency',
                'type',
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $id
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function show(int $id): Model|Collection|Builder|array|null
    {
        return ParcelOrder::with([
                'user',
                'currency',
                'deliveryman.deliveryManSetting',
                'transaction.paymentSystem',
                'galleries',
                'type',
                'review',
            ])
            ->find($id);
    }

    /**
     * @param ParcelOrder $parcelOrder
     * @return ParcelOrder|null
     */
    public function showByModel(ParcelOrder $parcelOrder): ?ParcelOrder
    {
        return $parcelOrder
            ->loadMissing([
                'user',
                'currency',
                'deliveryman.deliveryManSetting',
                'transaction.paymentSystem',
                'galleries',
                'type',
                'review',
            ]);
    }
}
