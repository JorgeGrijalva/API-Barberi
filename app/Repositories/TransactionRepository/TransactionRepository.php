<?php
declare(strict_types=1);

namespace App\Repositories\TransactionRepository;

use App\Models\Order;
use App\Models\Transaction;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Schema;

class TransactionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Transaction::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('transactions', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'payable',
                'user',
                'paymentSystem'
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $id
     * @param int|null $shopId
     * @return Transaction|null
     */
    public function show(int $id, ?int $shopId = null): ?Transaction
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return $this->model()
            ->with([
                'payable',
                'user',
                'paymentSystem'
            ])
            ->when($shopId, function (Builder $query, $shopId) {
                $query
                    ->whereHasMorph(
                        'payable',
                        Order::class,
                        fn($payable) => $payable->where('shop_id', $shopId)
                    );
            })
            ->find($id);
    }
}
