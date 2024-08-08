<?php
declare(strict_types=1);

namespace App\Repositories\DashboardRepository;

use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use App\Repositories\CoreRepository;
use Cache;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param $time
     * @param $filter
     * @return Builder|mixed
     */
    public function preDataStatistic($time, $filter): mixed
    {
        $shopId = data_get($filter, 'shop_id');
        $time   = now()->{$time}()->format('Y-m-d 00:00:01');

        return DB::table('orders')
            ->where('created_at', '>=', $time)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId));
    }

    /**
     * @param array $filter
     * @return array
     */
    public function ordersStatistics(array $filter = []): array
    {
        $time   = data_get($filter, 'time', 'subYear');
        $date   = date('Y-m-d 00:00:01');
        $shopId = data_get($filter, 'shop_id');

        $productsOutOfStock = Product::whereHas('stocks', fn($q) => $q->where('quantity', '<=', 0))
            ->when($shopId, fn($q) => $q->where('shop_id', '=', $shopId))
            ->count('id');

        $productsCount = Product::when($shopId, fn($q) => $q->where('shop_id', '=', $shopId))->count('id');

        $reviews = Review::when($shopId, fn($q) => $q
            ->whereHasMorph('assignable', Shop::class, function ($query) use($shopId) {
                $query->where('assignable_id', '=', $shopId);
            })
            ->whereHasMorph('reviewable', Shop::class, function ($query) use($shopId) {
                $query->where('id', '=', $shopId);
            }),
            fn($q) => $q->whereHasMorph('reviewable', Shop::class)
        )
            ->count('id');

        $orders = $this->preDataStatistic($time, $filter)
            ->select([
                DB::raw("count(if(created_at >= '$date', 1, null)) as today_count"),
                DB::raw("count(id) as orders_count"),
                DB::raw("count(if(status = 'canceled', 1, null)) as cancel_orders_count"),
                DB::raw("count(if(status = 'new', 1, null)) as new"),
                DB::raw("count(if(status = 'accepted', 1, null)) as accepted"),
                DB::raw("count(if(status = 'ready', 1, null)) as ready"),
                DB::raw("count(if(status = 'on_a_way', 1, null)) as on_a_way"),
                DB::raw("count(if(status = 'delivered', 1, null)) as delivered_orders_count"),
                DB::raw("count(if(status = 'new', 1, null) or if(status = 'accepted', 1, null) or if(status = 'ready', 1, null) or if(status = 'on_a_way', 1, null)) as progress_orders_count"),
                DB::raw("sum(if(status   = 'delivered', total_price, 0)) as total_earned"),
                DB::raw("sum(if(status   = 'delivered', total_tax, 0)) as tax_earned"),
                DB::raw("sum(if(status = 'delivered', commission_fee, 0)) as commission_earned"),
                DB::raw("sum(if(status = 'delivered', delivery_fee, 0)) as delivery_earned")
            ])
            ->first();

        return collect($orders)
            ->merge([
                'products_out_of_count' => $productsOutOfStock,
                'products_count'        => $productsCount,
                'reviews_count'         => $reviews,
            ])
            ->toArray();
    }

    public function ordersChart(array $filter = []): Collection
    {
        $time = data_get($filter, 'time', 'subYear');

        $timeFormat = $time == 'subYear' ? "%Y" : ($time == 'subMonth' ? "%Y-%m" : "%Y-%m-%d");

        return $this->preDataStatistic($time, $filter)
            ->select([
                DB::raw('sum(total_price) as total_price'),
                DB::raw('count(id) as count'),
                DB::raw("(DATE_FORMAT(created_at, '$timeFormat')) as time"),
            ])
            ->groupBy('time')
            ->get();
    }

    public function productsStatistic(array $filter = []): Paginator
    {
        $time    = data_get($filter, 'time', 'subYear');
        $time    = now()->{$time}()->format('Y-m-d 00:00:01');
        $perPage = $filter['perPage'] ?? 5;
        $locale  = Language::where('default', 1)->first(['locale', 'default'])?->locale;
        $shopId  = data_get($filter, 'shop_id');

        return Stock::with([
            'product:id,img',
            'product.translation' => fn($q) => $q
                ->select(['id', 'locale', 'title', 'product_id'])
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(function ($query) use ($locale) {
                        $query->where('locale', $this->language)->orWhere('locale', $locale);
                    });
                }),
            'stockExtras.value',
            'stockExtras.group.translation' => function ($q) use ($locale) {
                $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            }
        ])
            ->whereHas('orderDetails.order', function ($q) use ($time) {
                $q->where('status', Order::STATUS_DELIVERED)->whereDate('created_at', '>=', $time);
            })
            ->when($shopId, fn($q) => $q->whereHas('product', fn($q) => $q->where('shop_id', $shopId)))
            ->select([
                'id',
                'product_id',
                'price',
                'quantity',
            ])
            ->withCount([
                'orderDetails' => function ($q) use ($time) {
                    $q->whereDate('created_at', '>=', $time)->whereHas('order', function ($q) {
                        $q->where('status', Order::STATUS_DELIVERED);
                    });
                },
            ])
            ->orderBy('order_details_count', 'desc')
            ->simplePaginate($perPage);
    }

    public function usersStatistic(array $filter = []): Paginator
    {
        $time    = data_get($filter, 'time', 'subYear');
        $perPage = $filter['perPage'] ?? 5;

        $result  = $this->preDataStatistic($time, $filter)
            ->where('status', Order::STATUS_DELIVERED)
            ->select([
                DB::raw('user_id as user_id'),
                DB::raw('sum(total_price) as total_price'),
                DB::raw('count(id) as count'),
            ])
            ->groupBy('user_id')
            ->orderBy('total_price', 'desc')
            ->simplePaginate($perPage);

        $users = User::select([
            'firstname',
            'id',
            'img',
            'lastname',
            'phone',
        ])
            ->whereIn('id', collect($result->items())->pluck('user_id')->toArray())
            ->get();

        foreach ($result as $item) {

            $user = $users->where('id', $item->user_id)->first();

            $item->id = $user?->id ?? 'empty';

            if ($user?->img) {
                $item->img = $user->img;
            }

            if ($user?->phone) {
                $item->phone = $user->phone;
            }

            if ($user?->firstname) {
                $item->firstname = $user->firstname;
            }

            if ($user?->lastname) {
                $item->lastname = $user->lastname;
            }

            unset($item->user_id);

        }

        return $result;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function orderByStatusStatistics(array $filter = []): array
    {
        $new       = Order::STATUS_NEW;
        $accepted  = Order::STATUS_ACCEPTED;
        $ready     = Order::STATUS_READY;
        $onAWay    = Order::STATUS_ON_A_WAY;
        $delivered = Order::STATUS_DELIVERED;
        $canceled  = Order::STATUS_CANCELED;

        $date      = date('Y-m-d 00:00:01');

        $result    = [
            $new                    => 0,
            $accepted               => 0,
            $ready                  => 0,
            $onAWay                 => 0,
            $delivered              => 0,
            $canceled               => 0,
            'count'                 => 0,
            'total_price'           => 0,
            'today_count'           => 0,
            'total_delivered_price' => 0,
            'last_delivered_fee'    => 0,
        ];

        $status = data_get($filter, 'status');
        $total  = 0;

        unset($filter['status']);

        $orders = Order::filter($filter)
            ->select(['id', 'total_price', 'status', 'delivery_fee', 'created_at'])
            ->lazy(100);

        foreach ($orders as $order) {

            $result['count'] += 1;
            $result['total_price'] += $order->total_price;

            if ($order->status === $delivered) {
                $result['total_delivered_price'] += $order->delivery_fee;
            }

            if ($order->status === $delivered) {
                $result['last_delivered_fee'] = $order->delivery_fee;
            }

            if ($order->created_at >= $date) {
                $result['today_count'] += 1;
            }

            switch ($order->status) {
                case $delivered:
                    $result[$delivered] += 1;

                    if ($status === $delivered) {
                        $total += 1;
                    }
                    break;
                case $canceled:
                    $result[$canceled] += 1;

                    if ($status === $canceled) {
                        $total += 1;
                    }
                    break;
                case $new:
                    $result[$new] += 1;

                    if ($status === $new) {
                        $total += 1;
                    }
                    break;
                case $accepted:

                    $result[$accepted] += 1;

                    if ($status === $accepted) {
                        $total += 1;
                    }
                    break;
                case $ready:
                    $result[$ready] += 1;

                    if ($status === $ready) {
                        $total += 1;
                    }
                    break;
                case $onAWay:
                    $result[$onAWay] += 1;

                    if ($status === $onAWay) {
                        $total += 1;
                    }
                    break;
            }

        }

        $progress = $result[$new] + $result[$accepted] + $result[$ready] + $result[$onAWay];

        return [
            'new_orders_count'       => data_get($result, $new),
            'accepted_orders_count'  => data_get($result, $accepted),
            'ready_orders_count'     => data_get($result, $ready),
            'on_a_way_orders_count'  => data_get($result, $onAWay),
            'delivered_orders_count' => data_get($result, $delivered),
            'cancel_orders_count'    => data_get($result, $canceled),
            'progress_orders_count'  => $progress,
            'total_delivered_price'  => data_get($result, 'total_delivered_price'),
            'last_delivered_fee'     => data_get($result, 'last_delivered_fee'),
            'orders_count'           => data_get($result, 'count'),
            'total_price'            => data_get($result, 'total_price'),
            'today_count'            => data_get($result, 'today_count'),
            'total'                  => !$status ? data_get($result, 'count') : $total,
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function salesReport(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from', now()->toString())));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $shopId   = data_get($filter, 'shop_id');
        $column   = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('orders', $column) ? $column : 'id';
        }

        $orders = Order::with([
            'orderDetails'
        ])
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
            ->where([
                ['created_at', '>=', $dateFrom],
                ['created_at', '<=', $dateTo],
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->select([
                DB::raw('id'),
                DB::raw('total_price'),
                DB::raw('created_at'),
                DB::raw('status'),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m-%d')) as time")
            ])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $commissionFee = 0;
        $serviceFee    = 0;
        $totalPrice    = 0;
        $canceledFee   = $orders->where('status', Order::STATUS_CANCELED)->sum('total_price');

        $deliveredOrders = $orders->where('status', Order::STATUS_DELIVERED);

        foreach ($deliveredOrders as $order) {
            $commissionFee += $order->sum('commission_fee');
            $serviceFee    += $order->sum('service_fee');
            $totalPrice    += $order->sum('total_price');
        }

        return [
            'card' => [
                'total_price'  => $totalPrice,
                'seller_fee'   => $totalPrice - $serviceFee - $commissionFee,
                'canceled_fee' => $canceledFee,
            ],
            'chart' => $deliveredOrders->groupBy('time'),
        ];
    }
}
