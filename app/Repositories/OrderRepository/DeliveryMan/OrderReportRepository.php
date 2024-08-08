<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository\DeliveryMan;

use App\Helpers\OrderReportHelper;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\DB;

class OrderReportRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function report(array $filter = []): array
    {
        $type       = data_get($filter, 'type', 'day');
        $dateFrom   = date('Y-m-d 00:00:01', strtotime(request('date_from')));
        $dateTo     = date('Y-m-d 23:59:59', strtotime(request('date_to', now()->toString())));
        $now        = now()?->format('Y-m-d 00:00:01');

        /** @var User $user */
        $user       = User::with(['wallet'])->find(data_get($filter, 'deliveryman_id'));

        $lastOrder  = DB::table('orders')
            ->where('deliveryman_id', data_get($filter, 'deliveryman_id'))
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->latest('id')
            ->first();

        $orders = DB::table('orders')
            ->where('deliveryman_id', data_get($filter, 'deliveryman_id'))
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->select(array_merge([
                DB::raw("sum(if(status = 'delivered', delivery_fee, 0)) as delivery_fee"),
                DB::raw('count(id) as total_count'),
                DB::raw("sum(if(created_at >= '$now', 1, 0)) as total_today_count"),
            ], OrderReportHelper::rawPricesByOrderStatuses()))
            ->first();

        $type = match ($type) {
            'year'  => '%Y',
            'week'  => '%w',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $chart = DB::table('orders')
            ->where('deliveryman_id', data_get($filter, 'deliveryman_id'))
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->where('status', Order::STATUS_DELIVERED)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('sum(delivery_fee) as total_price'),
            ])
            ->groupBy('time')
            ->orderBy('time')
            ->get();

        return [
            'last_order_total_price'    => (int)ceil(data_get($lastOrder, 'total_price', 0)),
            'last_order_income'         => (int)ceil(data_get($lastOrder, 'delivery_fee', 0)),
            'total_price'               => (int)data_get($orders, 'delivery_fee', 0),
            'avg_rating'                => $user->r_avg,
            'wallet_price'              => $user->wallet?->price,
            'wallet_currency'           => $user->wallet?->currency,
            'total_count'               => (int)data_get($orders, 'total_count', 0),
            'total_today_count'         => (int)data_get($orders, 'total_today_count', 0),
            'total_new_count'           => (int)data_get($orders, 'total_new_count', 0),
            'total_ready_count'         => (int)data_get($orders, 'total_ready_count', 0),
            'total_on_a_way_count'      => (int)data_get($orders, 'total_on_a_way_count', 0),
            'total_pause_count'         => (int)data_get($orders, 'total_pause_count', 0),
            'total_accepted_count'      => (int)data_get($orders, 'total_accepted_count', 0),
            'total_canceled_count'      => (int)data_get($orders, 'total_canceled_count', 0),
            'total_delivered_count'     => (int)data_get($orders, 'total_delivered_count', 0),
            'chart'                     => $chart
        ];
    }

}
