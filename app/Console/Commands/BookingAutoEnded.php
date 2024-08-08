<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use DB;
use Illuminate\Console\Command;
use Throwable;

class BookingAutoEnded extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:auto:ended';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'booking auto ended';

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
     * @return int
     */
    public function handle(): int
    {
        try {
            DB::table('bookings')
                ->where('end_date', '<=', now()->format('Y-m-d H:i:s'))
                ->whereIn('status', [Booking::STATUS_NEW, Booking::STATUS_BOOKED, Booking::STATUS_PROGRESS])
                ->update([
                    'status' => Booking::STATUS_ENDED
                ]);
        } catch (Throwable $e) {
            $this->error($e);
        }
        return 0;
    }
}
