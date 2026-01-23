<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ReconcileReelBalances::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Run daily reel balance reconciliation at 2:00 AM
        $schedule->command('reels:reconcile --auto-fix')
            ->dailyAt('02:00')
            ->appendOutputTo(storage_path('logs/reel-reconciliation.log'));

        // Generate weekly reconciliation report (Sundays at 23:00)
        $schedule->command('reels:reconcile --report-only')
            ->weeklyOn(0, '23:00')  // 0 = Sunday
            ->appendOutputTo(storage_path('logs/reel-reconciliation-weekly.log'))
            ->emailOutputTo('admin@example.com');  // Update with actual admin email
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
