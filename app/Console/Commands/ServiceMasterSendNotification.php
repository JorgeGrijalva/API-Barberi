<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\PushNotification;
use App\Traits\Notification;
use Illuminate\Console\Command;
use App\Models\ServiceMasterNotification;

class ServiceMasterSendNotification extends Command
{
    use Notification;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:master:send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send service master notification';

    /**
     * @var string
     */
    protected $language = 'en';

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
        ServiceMasterNotification::chunkMap(function (ServiceMasterNotification $model) {

            $time = "$model->notification_time $model->notification_type";

            $lastSendAt = date('Y-m-d H:i', strtotime("- $time"));

            if (date('Y-m-d H:i', strtotime($model->last_sent_at)) !== $lastSendAt) {
                return;
            }

            /** @var Booking[] $bookings */
            $bookings = Booking::with(['user:id,firebase_token,lang'])
                ->whereHas('user:id')
                ->where('service_master_id', $model->service_master_id)
                ->where('status', Booking::STATUS_ENDED)
                ->select('user_id')
                ->get();

            foreach ($bookings as $booking) {

                $this->language = $booking->user?->lang;

                $this->sendNotification(
                    $model,
                    $booking->user?->firebase_token ?? [],
                    $model->translation->title,
                    $model->translation->title,
                    [
                        'id'   => $model->id,
                        'type' => PushNotification::BOOKING_NOTIFICATION
                    ],
                    [$booking->user_id]
                );

            }

            $model->update(['last_sent_at' => date('Y-m-d H:i')]);

        });
    }
}

