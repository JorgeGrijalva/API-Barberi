<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MasterClosedDate;
use Illuminate\Console\Command;
use Log;
use Throwable;

class RemoveExpiredMasterClosedDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:master:closed:dates';

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
        $dates = MasterClosedDate::where('date', '<=', date('Y-m-d', strtotime('-1 day')))->get();

        foreach ($dates as $date) {
            try {
                $date->delete();
            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }
        }

        return 0;
    }
}
