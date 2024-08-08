<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

use App\Helpers\OrderReportHelper;
use App\Models\Order;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\DB;

class SellerOrderReportRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function report(array $filter = []): array
    {
        $type     = data_get($filter, 'type', 'day');
        $dateFrom = date('Y-m-d 00:00:01', strtotime(request('date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(request('date_to', now()->toString())));
        $now      = now()?->format('Y-m-d 00:00:01');

        $orders = DB::table('orders')
            ->where('shop_id', $filter['shop_id'])
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->select(array_merge([
                DB::raw("sum(if(status = 'delivered', total_price, 0)) as total_price"),
                DB::raw("sum(if(status = 'delivered', commission_fee, 0)) as commission_fee"),
                DB::raw("sum(if(status = 'delivered', delivery_fee, 0)) as delivery_fee"),
                DB::raw('count(id) as total_count'),
                DB::raw("sum(if(created_at >= '$now', 1, 0)) as total_today_count"),
            ], OrderReportHelper::rawPricesByOrderStatuses()))
            ->first();

        $fmTotalPrice = data_get($orders, 'total_price', 0)
            - data_get($orders, 'commission_fee', 0)
            - data_get($orders, 'delivery_fee', 0);

        $type = match ($type) {
            'year'  => '%Y',
            'week'  => '%w',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $chart = DB::table('orders')
            ->where('shop_id', $filter['shop_id'])
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->where('status', Order::STATUS_DELIVERED)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('sum(total_price) as total_price'),
            ])
            ->groupBy('time')
            ->orderBy('time')
            ->get();

        $lastOrder = Order::where('shop_id', $filter['shop_id'])->latest('id')->first();

        return [
            'last_order_total_price' => (int)ceil($lastOrder?->total_price) ?? 0,
            'last_order_income'      => (int)ceil($lastOrder?->seller_fee) ?? 0,
            'total_price'            => (int)data_get($orders, 'total_price', 0),
            'fm_total_price'         => (int)$fmTotalPrice,
            'total_count'            => (int)data_get($orders, 'total_count', 0),
            'total_today_count'      => (int)data_get($orders, 'total_today_count', 0),
            'total_new_count'        => (int)data_get($orders, 'total_new_count', 0),
            'total_ready_count'      => (int)data_get($orders, 'total_ready_count', 0),
            'total_on_a_way_count'   => (int)data_get($orders, 'total_on_a_way_count', 0),
            'total_pause_count'      => (int)data_get($orders, 'total_pause_count', 0),
            'total_accepted_count'   => (int)data_get($orders, 'total_accepted_count', 0),
            'total_canceled_count'   => (int)data_get($orders, 'total_canceled_count', 0),
            'total_delivered_count'  => (int)data_get($orders, 'total_delivered_count', 0),
            'chart'                  => $chart
        ];
    }

}
