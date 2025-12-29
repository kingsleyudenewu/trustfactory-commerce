<?php

use App\Jobs\GenerateDailySalesReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
*/

// Run daily sales report every evening at 6 PM (18:00)
Schedule::job(new GenerateDailySalesReportJob())
    ->dailyAt('18:00')
    ->name('generate-daily-sales-report')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Daily sales report scheduled job failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Daily sales report scheduled job succeeded');
    });

// Clean up old notifications (older than 90 days) every day at midnight
Schedule::command('notification:cleanup')
    ->daily()
    ->at('00:00')
    ->name('cleanup-old-notifications')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Notification cleanup job failed');
    });
