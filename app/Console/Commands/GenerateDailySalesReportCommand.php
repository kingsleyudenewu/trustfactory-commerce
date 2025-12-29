<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailySalesReportJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailySalesReportCommand extends Command
{
    protected $signature = 'report:daily-sales {date? : Report date in Y-m-d format (defaults to yesterday)}';

    protected $description = 'Generate and send daily sales report';

    public function handle(): int
    {
        $dateString = $this->argument('date');
        
        if ($dateString) {
            try {
                $reportDate = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
            } catch (\Exception $e) {
                $this->error("Invalid date format. Please use Y-m-d format (e.g., 2025-12-26)");
                return self::FAILURE;
            }
        } else {
            // Default to yesterday
            $reportDate = today()->subDay();
        }

        $this->info("Generating daily sales report for {$reportDate->format('F j, Y')}...\n");

        try {
            GenerateDailySalesReportJob::dispatch($reportDate);
            $this->info("✓ Daily sales report job dispatched successfully");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch job: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
