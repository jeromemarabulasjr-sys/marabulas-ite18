<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These commands will run automatically based on the schedule defined below.
     * For the scheduler to work, you need to add a cron entry:
     *
     * * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
     *
     * On Windows with Task Scheduler, run every minute:
     * php artisan schedule:run
     */
    protected function schedule(Schedule $schedule): void
    {
        // Calculate monthly summary at the start of each month (1st day at 00:05)
        // This automatically calculates the previous month's in/out transactions
        $schedule->command('inventory:calculate-monthly --auto')
            ->monthlyOn(1, '00:05')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/monthly-summary.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
