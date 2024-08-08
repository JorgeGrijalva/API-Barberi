<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\ResponseError;
use App\Models\Booking;
use App\Models\Language;
use App\Models\NotificationUser;
use App\Models\PushNotification;
use App\Traits\Notification;
use Illuminate\Console\Command;

class BookingNotification extends Command
{
    use Notification;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send booking notification';
    protected string $language;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        $lang = Language::where('default',1)->first();
        $this->language = $lang?->locale ?? 'en';

        $bookings = Booking::whereIn('status', [Booking::STATUS_NEW, Booking::STATUS_BOOKED])
            ->where('start_date','=', now()->addMinutes(30))
            ->get();

        /** @var Booking $booking */

        foreach ($bookings as $booking) {

            $title = __(
                'errors.' . ResponseError::BOOKING_NOTIFICATION, locale: $booking->user->lang ?? $lang->locale
            );

            /** @var NotificationUser $notification */

            $notification = $booking->user?->notifications?->where('type', \App\Models\Notification::PUSH)?->first();

            if (!$notification?->notification?->active) {
                return;
            }

            $this->sendNotification(
                $booking,
                $booking->user?->firebase_token,
                $title,
                $title,
                [
                    'id'     => $booking->id,
                    'status' => $booking->status,
                    'type'   => PushNotification::BOOKING_NOTIFICATION
                ],
                [$booking->user_id]
            );
        }

    }
}
