<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeliveryPointClosedDate;
use Illuminate\Console\Command;
use Log;
use Throwable;

class RemoveExpiredDeliveryPointClosedDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:delivery:point:closed:dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expired closed dates';

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
        $dates = DeliveryPointClosedDate::where('date', '<=', date('Y-m-d', strtotime('-1 day')))->get();

        foreach ($dates as $value) {
            try {
                $value->delete();
            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }
        }

        return 0;
    }
}
