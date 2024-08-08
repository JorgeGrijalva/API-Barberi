<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Booking;
use App\Services\ModelLogService\ModelLogService;

class BookingObserver
{
    /**
     * Handle the Brand "created" event.
     *
     * @param Booking $booking
     * @return void
     */
    public function created(Booking $booking): void
    {
        (new ModelLogService)->logging($booking, $booking->getAttributes(), 'created');
    }

    /**
     * Handle the Brand "updated" event.
     *
     * @param Booking $booking
     * @return void
     */
    public function updated(Booking $booking): void
    {
        (new ModelLogService)->logging($booking, $booking->getAttributes(), 'updated');
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param Booking $booking
     * @return void
     */
    public function deleted(Booking $booking): void
    {
        (new ModelLogService)->logging($booking, $booking->getAttributes(), 'deleted');
    }

}
