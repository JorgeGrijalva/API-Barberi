<?php
declare(strict_types=1);

namespace App\Repositories\ReportRepository;

use App\Models\Booking;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopAdsPackage;
use App\Models\ShopSubscription;
use App\Models\Transaction;
use App\Models\User;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Schema;

class BookingRepository
{
    /**
     * @param array $filter
     * @return Collection
     */
    public function payments(array $filter): Collection
    {
        $filter['model'] = 'bookings';

        return Transaction::filter($filter)
            ->select([
                'payment_sys_id',
                DB::raw("sum(if(status='progress', price, 0)) as progress_price"),
                DB::raw("sum(if(status='paid',     price, 0)) as paid_price"),
                DB::raw("sum(if(status='canceled', price, 0)) as canceled_price"),
                DB::raw("sum(if(status='rejected', price, 0)) as rejected_price"),
                DB::raw("sum(if(status='refund',   price, 0)) as refund_price"),
            ])
            ->where('payable_type', Booking::class)
            ->groupBy('payment_sys_id')
            ->get()
            ->map(function (object $item) {

                /** @var Payment $payments */
                $payments = DB::table('payments')
                    ->select(['tag'])
                    ->where('id', $item->payment_sys_id)
                    ->first();

                $item->payment_name = $payments?->tag;

                return $item;
            });
    }

    /**
     * @param array $filter
     * @return Collection
     */
    public function summary(array $filter): Collection
    {
        $shopIdExistTypes = [
            Booking::class,
            Order::class,
            ShopAdsPackage::class,
            ShopSubscription::class
        ];

        $dateFrom = $filter['date_from'] ?? now()->format('Y-m-d');
        $dateTo   = $filter['date_to']   ?? now()->format('Y-m-d');

        return Transaction::select([
                'payable_type',
                DB::raw('sum(price) as total_price'),
                DB::raw("sum(if(status='progress', price, 0)) as progress_price"),
                DB::raw("sum(if(status='paid',     price, 0)) as paid_price"),
                DB::raw("sum(if(status='canceled', price, 0)) as canceled_price"),
                DB::raw("sum(if(status='rejected', price, 0)) as rejected_price"),
                DB::raw("sum(if(status='refund',   price, 0)) as refund_price"),
            ])
            ->when(data_get($filter, 'shop_id'), function (Builder $q, $shopId) use ($shopIdExistTypes) {
                return $q->whereHasMorph('payable', $shopIdExistTypes, fn($q) => $q->where('shop_id', $shopId));
            })
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->groupBy('payable_type')
            ->get()
            ->map(function (object $item) {

                $item->model_name     = str_replace('App\\Models\\', '', $item->payable_type);
                $item->total_price    = round($item->total_price,    1);
                $item->progress_price = round($item->progress_price, 1);
                $item->paid_price     = round($item->paid_price,     1);
                $item->canceled_price = round($item->canceled_price, 1);
                $item->rejected_price = round($item->rejected_price, 1);
                $item->refund_price   = round($item->refund_price,   1);

                unset($item['payable_type']);

                return $item;
            });
    }

    /**
     * @param array $filter
     * @return array
     */
    public function cards(array $filter): array
    {
        $dateFrom = $filter['date_from'] ?? now()->format('Y-m-d');
        $dateTo   = $filter['date_to']   ?? now()->format('Y-m-d');

        $bookings = DB::table('bookings')
            ->where('shop_id', $filter['shop_id'])
            ->whereDate('start_date', '>=', $dateFrom)
            ->whereDate('end_date', '<=', $dateTo)
            ->select([
                DB::raw('sum(price - discount + commission_fee + service_fee) as total_price'),
                DB::raw("sum(if(status = 'ended', price - discount + commission_fee + service_fee, 0)) as ended_total_price"),
                DB::raw("avg(if(status = 'ended', price - discount + commission_fee + service_fee, 0)) as average_total_price"),
            ])
            ->first();

        /** @var object $bookings */
        return [
            'total_price'         => $bookings->total_price ?: 0,
            'ended_total_price'   => $bookings->ended_total_price ?: 0,
            'average_total_price' => $bookings->average_total_price ?: 0,
        ];
    }

    /**
     * @param array $filter
     * @return Collection
     */
    public function chart(array $filter): Collection
    {
        $dateFrom = $filter['date_from'] ?? now()->format('Y-m-d');
        $dateTo   = $filter['date_to']   ?? now()->format('Y-m-d');

        $type = match ($filter['type']) {
            'year'  => '%Y',
            'month' => '%Y-%m-%d',
            'week'  => '%Y-%m-%d %w',
            default => '%Y-%m-%d %H:00',
        };

        return DB::table('bookings')
            ->where('shop_id', $filter['shop_id'])
            ->whereDate('start_date', '>=', $dateFrom)
            ->whereDate('end_date', '<=', $dateTo)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw("sum(if(status='ended',    price - discount + commission_fee + service_fee, 0)) as total_price"),
                DB::raw("avg(if(status='ended',    price - discount + commission_fee + service_fee, 0)) as average_total_price"),
                DB::raw("sum(if(status='canceled', price - discount + commission_fee + service_fee, 0)) as canceled_total_price"),
                DB::raw("avg(if(status='canceled', price - discount + commission_fee + service_fee, 0)) as canceled_avg_total_price"),
            ])
            ->groupBy('time')
            ->orderBy('time')
            ->get();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function masters(array $filter): LengthAwarePaginator
    {
        $dateFrom = $filter['date_from'] ?? now()->format('Y-m-d');
        $dateTo   = $filter['date_to']   ?? now()->format('Y-m-d');
        $column   = $filter['column']    ?? 'r_avg';

        if ($column !== 'r_avg') {
            $column = Schema::hasColumn('users', $column) ? $column : 'r_avg';
        }

        //Booking::with(['master:id,firstname,lastname,r_avg'])
        //            ->select([
        //                'id',
        //                'shop_id',
        //                'master_id',
        //                'start_date',
        //                'end_date',
        //                'price',
        //                'discount',
        //                'commission_fee',
        //                'service_fee',
        //                'status',
        //                'rate'
        //            ])
        //            ->where('shop_id', $filter['shop_id'])
        //            ->whereDate('start_date', '>=', $dateFrom)
        //            ->whereDate('end_date',   '<=', $dateTo)
        //            ->orderBy($column, $filter['sort'] ?? 'desc')
        //            ->paginate($filter['perPage'] ?? 5);
        return User::with([
            'masterBookings' => function ($q) use ($dateFrom, $dateTo) {
                $q
                    ->select([
                        'id', 'master_id', 'start_date', 'end_date', 'price',
                        'discount', 'commission_fee', 'service_fee', 'status', 'rate'
                    ])
                    ->whereDate('start_date', '>=', $dateFrom)
                    ->whereDate('end_date', '<=', $dateTo);
            },
        ])
            ->whereHas('invite', function ($q) use ($filter) {
                $q->where('shop_id', $filter['shop_id'])->where('status', Invitation::ACCEPTED);
            })
            ->whereHas('masterBookings', function ($q) use ($dateFrom, $dateTo) {
                $q->whereDate('start_date', '>=', $dateFrom)->whereDate('end_date', '<=', $dateTo);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 5);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function statistic(array $filter): array
    {
        $dateFrom = $filter['date_from'] ?? now()->format('Y-m-d');
        $dateTo   = $filter['date_to']   ?? now()->format('Y-m-d');

        $bookings = DB::table('bookings')
            ->select([
                DB::raw("count(id) as count"),
                DB::raw("sum(total_price) as total_prices"),
                DB::raw("sum(if(status='new',      1, 0))           as new_total_count"),
                DB::raw("sum(if(status='booked',   1, 0))           as booked_total_count"),
                DB::raw("sum(if(status='progress', 1, 0))           as progress_total_count"),
                DB::raw("sum(if(status='ended',    1, 0))           as ended_total_count"),
                DB::raw("sum(if(status='canceled', 1, 0))           as canceled_total_count"),
                DB::raw("sum(if(status='new',      total_price, 0)) as new_total_price"),
                DB::raw("sum(if(status='booked',   total_price, 0)) as booked_total_price"),
                DB::raw("sum(if(status='progress', total_price, 0)) as progress_total_price"),
                DB::raw("sum(if(status='ended',    total_price, 0)) as ended_total_price"),
                DB::raw("sum(if(status='canceled', total_price, 0)) as canceled_total_price"),
            ])
            ->when(data_get($filter, 'shop_id'),   fn($q) => $q->where('shop_id',   $filter['shop_id']))
            ->when(data_get($filter, 'master_id'), fn($q) => $q->where('master_id', $filter['master_id']))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->first();


        $totalCount = $bookings?->count;
        $totalPrice = (double)$bookings?->total_prices;

        $newCount        = (int) $bookings?->new_total_count;
        $bookedCount     = (int) $bookings?->booked_total_count;
        $progressCount   = (int) $bookings?->progress_total_count;
        $endedCount      = (int) $bookings?->ended_total_count;
        $canceledCount   = (int) $bookings?->canceled_total_count;

        $newPrice        = (double)$bookings?->new_total_price;
        $bookedPrice     = (double)$bookings?->booked_total_price;
        $progressPrice   = (double)$bookings?->progress_total_price;
        $endedPrice      = (double)$bookings?->ended_total_price;
        $canceledPrice   = (double)$bookings?->canceled_total_price;

        $newPercent      = $newCount      > 0 ? (double) number_format($newCount      / $totalCount * 100, 2) : 0;
        $bookedPercent   = $bookedCount   > 0 ? (double) number_format($bookedCount   / $totalCount * 100, 2) : 0;
        $progressPercent = $progressCount > 0 ? (double) number_format($progressCount / $totalCount * 100, 2) : 0;
        $endedPercent    = $endedCount    > 0 ? (double) number_format($endedCount    / $totalCount * 100, 2) : 0;
        $canceledPercent = $canceledCount > 0 ? (double) number_format($canceledCount / $totalCount * 100, 2) : 0;

        $groupActive           = ($newCount + $bookedCount + $progressCount);
        $groupNotActive        = ($endedCount + $canceledCount);
        $groupActivePercent    = (double) number_format(($newPercent + $bookedPercent + $progressPercent), 2);
        $groupNotActivePercent = (double) number_format(($endedPercent + $canceledPercent), 2);

        return [
            'total_count' => $totalCount,
            'total_price' => $totalPrice,
            'new' => [
                'count'       => $newCount,
                'percent'     => $newPercent,
                'total_price' => $newPrice
            ],
            'booked' => [
                'count'       => $bookedCount,
                'percent'     => $bookedPercent,
                'total_price' => $bookedPrice
            ],
            'progress' => [
                'count'       => $progressCount,
                'percent'     => $progressPercent,
                'total_price' => $progressPrice
            ],
            'canceled' => [
                'count'       => $canceledCount,
                'percent'     => $canceledPercent,
                'total_price' => $canceledPrice
            ],
            'ended' => [
                'count'       => $endedCount,
                'percent'     => $endedPercent,
                'total_price' => $endedPrice
            ],
            'group' => [
                'active' => [
                    'count'   => $groupActive,
                    'percent' => $groupActivePercent,
                ],
                'ended' => [
                    'count'   => $groupNotActive,
                    'percent' => $groupNotActivePercent,
                ]
            ]
        ];
    }
}
