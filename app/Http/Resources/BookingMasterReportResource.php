<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingMasterReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var User|JsonResource $this */
        [
            $canceledCount, $canceledPrice,
            $endedCount,    $endedPrice,
            $processCount,  $processPrice,
            $count,
        ] = 0;

        $processStatuses = [Booking::STATUS_NEW, Booking::STATUS_BOOKED, Booking::STATUS_PROGRESS];

        $this->masterBookings?->map(function (Booking $booking) use(
            &$canceledCount, &$canceledPrice,
            &$endedCount,    &$endedPrice,
            &$processCount,  &$processPrice,
            &$count,          $processStatuses
        ) {

            $count++;

            if (in_array($booking->status, $processStatuses)) {
                $processCount += 1;
                $processPrice += $booking->total_price;
            } elseif ($booking->status === Booking::STATUS_ENDED) {
                $endedCount += 1;
                $endedPrice += $booking->total_price;
            } elseif ($booking->status === Booking::STATUS_CANCELED) {
                $canceledCount += 1;
                $canceledPrice += $booking->total_price;
            }

        });

        return [
            'id'             => $this->id,
            'firstname'      => $this->firstname,
            'lastname'       => $this->lastname,
            'r_avg'          => $this->r_avg ?? 0,
            'count'          => $count, // $this->masterBookings?->count()
            'process_count'  => round((double)$processCount,  1),
            'process_price'  => round((double)$processPrice,  1),
            'ended_count'    => round((double)$endedCount,    1),
            'ended_price'    => round((double)$endedPrice,    1),
            'canceled_count' => round((double)$canceledCount, 1),
            'canceled_price' => round((double)$canceledPrice, 1),
        ];
    }

}
