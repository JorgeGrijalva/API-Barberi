<?php
declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
         $schedule->command('email:send:by:time')->hourly();
         $schedule->command('booking:send:notification')->everyMinute();
         $schedule->command('remove:expired:closed:dates')->dailyAt('11:59');
         $schedule->command('remove:expired:delivery:point:closed:dates')->dailyAt('11:59');
         $schedule->command('remove:expired:stories')->dailyAt('11:59');
         $schedule->command('remove:expired:master:disabled:times')->dailyAt('11:59');
         $schedule->command('remove:expired:models')->hourly();
         $schedule->command('booking:auto:ended')->hourly();
         $schedule->command('remove:expired:master:closed:dates')->everySixHours();
         $schedule->command('remove:expired:warehouse:closed:dates')->hourly();
         $schedule->command('service:master:send:notification')->everyMinute();
//         $schedule->command('truncate:telescope')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
