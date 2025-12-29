<?php

namespace App\Console\Commands;

use App\Models\ProductLowStockNotification;
use App\Models\DailySalesReport;
use Illuminate\Console\Command;

class CleanupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:cleanup {--days=90 : Delete records older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notification records and daily sales reports';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up records older than {$days} days (before {$cutoffDate->toDateString()})...");

        // Clean up old low stock notifications
        $notificationsDeleted = ProductLowStockNotification::where('created_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$notificationsDeleted} old low stock notifications.");

        // Clean up old daily sales reports
        $reportsDeleted = DailySalesReport::where('created_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$reportsDeleted} old daily sales reports.");

        $this->info('Cleanup completed successfully.');

        return Command::SUCCESS;
    }
}
