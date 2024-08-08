<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MasterDisabledTime;
use Illuminate\Console\Command;
use Log;
use Throwable;

class RemoveExpiredMasterDisabledTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:master:disabled:times';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expired disabled times';

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
        $now   = date('Y-m-d H:i');
        $class = MasterDisabledTime::class;

        $dates = $class::get();

        foreach ($dates as $date) {

            try {
                $dateFrom = date('Y-m-d H:i', strtotime("$date->date $date->from"));
                $dateTo   = date('Y-m-d H:i', strtotime("$date->date $date->to"));

                if ($date->repeats === $class::DONT_REPEAT && $dateFrom <= $now && $dateTo <= $now) {
                    $date->delete();
                }

                if ($date->repeats === $class::DAY && $date->end_type === $class::DATE && $date->end_value <= $now) {
                    $date->delete();
                }

                if ($date->repeats === $class::CUSTOM && $date->end_type === $class::DATE && $date->end_value <= $now) {
                    $date->delete();
                }

                $customFormat = strtotime("$date->date +$date->end_value $date->repeats");

                if (
                    in_array($date->repeats, $class::CUSTOM_REPEAT_TYPE)
                    && $date->end_type === $class::AFTER
                    && date('Y-m-d H:i', $customFormat) <= $now
                ) {
                    $date->delete();
                }

                $customFormat = strtotime("$date->date +$date->end_value $date->custom_repeat_type");

                if (
                    $date->repeats === $class::CUSTOM
                    && $date->end_type === $class::AFTER
                    && date('Y-m-d H:i', $customFormat) <= $now
                ) {
                    $date->delete();
                }

            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }

        }

        return 0;
    }
}
