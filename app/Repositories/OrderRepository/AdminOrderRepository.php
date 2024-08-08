<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Repositories\CoreRepository;
use App\Traits\SetCurrency;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

class AdminOrderRepository extends CoreRepository
{
    use SetCurrency;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function ordersPaginate(array $filter = []): Paginator
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
            ->withCount('orderDetails')
            ->withSum('children', 'total_price')
            ->with([
                'user:id,lastname,firstname,img,email,phone',
                'deliveryman:id,lastname,firstname,img,email,phone',
                'transaction.paymentSystem',
                'currency',
                'orderDetails'
            ])
            ->filter($filter)
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function userOrdersPaginate(array $filter = []): Paginator
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
            ->withCount('orderDetails')
            ->withSum('children', 'total_price')
            ->with((new OrderRepository)->getWith($filter['user_id'] ?? null))
            ->filter($filter)
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param string $id
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function userOrder(string $id, array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var User $user */
        $user = User::select(['id', 'uuid'])->where('uuid', $id)->first();

        return OrderDetail::with([
            'stock.stockExtras.value',
            'stock.stockExtras.group.translation' => fn($q) => $q->select('id', 'extra_group_id', 'locale', 'title'),
            'stock.product' => fn($q) => $q->select('id', 'uuid', 'img', 'status', 'active'),
            'stock.product.translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ])
            ->whereHas('order', fn($q) => $q->where('user_id', $user?->id))
            ->groupBy(['stock_id'])
            ->select([
                'stock_id',
                DB::raw('count(stock_id) as count'),
                DB::raw('sum(total_price) as total_price')
            ])
            ->orderBy('count', 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

}
