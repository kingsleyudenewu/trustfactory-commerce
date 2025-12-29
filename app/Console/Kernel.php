<?php

namespace App\Console;

use App\Jobs\GenerateDailySalesReportJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run daily sales report every evening at 6 PM (18:00)
        $schedule->job(new GenerateDailySalesReportJob())
            ->dailyAt('18:00')
            ->name('generate-daily-sales-report')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Daily sales report scheduled job failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Daily sales report scheduled job succeeded');
            });

        // Optional: Clean up old notifications (older than 90 days)
        $schedule->command('notification:cleanup')
            ->daily()
            ->at('00:00')
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Notification cleanup job failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
