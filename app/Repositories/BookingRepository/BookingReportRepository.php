<?php
declare(strict_types=1);

namespace App\Repositories\BookingRepository;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Repositories\CoreRepository;

class BookingReportRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Booking::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function reportTransactions(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from', '-30 days')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
        $shopId   = data_get($filter, 'shop_id');

        $coupon			= 0;
        $commissionFee  = 0;
        $serviceFee 	= 0;
        $extraTimePrice = 0;
        $totalPrice 	= 0;

        $bookings = Booking::with([
            'transaction.paymentSystem:id,tag',
            'shop.trans',
            'shop.seller',
            'user:id,firstname,lastname',
        ])
            ->where([
                ['created_at', '>=', $dateFrom],
                ['created_at', '<=', $dateTo],
                ['status', Booking::STATUS_ENDED]
            ])
            ->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'type'), function($q, $type) use ($filter) {

                $q
                    ->whereHas('shop', function ($q) use ($filter) {
                        $q
                            ->whereHas('seller')
                            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId));
                    })
                    ->whereDoesntHave('paymentToPartner', fn($q) => $q->where('type', $type));

            });

        $bookings->chunkMap(
            function (Booking $booking) use (
                &$coupon,
                &$commissionFee,
                &$serviceFee,
                &$extraTimePrice,
                &$totalPrice,
            ) {
                $totalPrice     += $booking->total_price;
                $serviceFee     += $booking->service_fee;
                $coupon         += $booking->coupon_price;
                $extraTimePrice += $booking->extra_time_price;
                $commissionFee  += $booking->commission_fee;
            });

        $bookings = $bookings->paginate($filter['perPage'] ?? 10);

        return [
            'total_coupon'			=> $coupon,
            'total_commission_fee'	=> $commissionFee,
            'total_service_fee'		=> $serviceFee,
            'total_price'			=> $totalPrice,
            'extra_time_price'  	=> $extraTimePrice,
            'total_seller_fee' 		=> $totalPrice - $serviceFee - $commissionFee - $coupon,
            'data' 					=> BookingResource::collection($bookings),
            'meta'					=> [
                'page'		=> $bookings->currentPage(),
                'perPage'	=> $bookings->perPage(),
                'total'		=> $bookings->total(),
            ]
        ];
    }

}
