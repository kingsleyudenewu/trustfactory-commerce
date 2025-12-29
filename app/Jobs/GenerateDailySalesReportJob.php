<?php

namespace App\Jobs;

use App\Mail\DailySalesReportMail;
use App\Models\CartItem;
use App\Models\DailySalesReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateDailySalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    public function __construct(public ?Carbon $reportDate = null)
    {
        $this->reportDate ??= today();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get admin user
        $admin = User::where('email', config('app.admin_email'))->first();
        
        if (!$admin) {
            \Illuminate\Support\Facades\Log::error('Admin user not found for daily sales report', [
                'admin_email' => config('app.admin_email'),
            ]);
            return;
        }

        // Get all cart items created yesterday (for previous day report)
        $reportDate = $this->reportDate;
        $startOfDay = $reportDate->copy()->startOfDay();
        $endOfDay = $reportDate->copy()->endOfDay();

        $cartItems = CartItem::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->with('product')
            ->get();

        // Calculate metrics
        $totalItemsSold = $cartItems->sum('quantity');
        $totalRevenue = $cartItems->sum(fn ($item) => $item->quantity * $item->product->price);
        $uniqueProductsSold = $cartItems->groupBy('product_id')->count();

        // Get top 10 products
        $topProducts = $cartItems
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'product_id' => $items->first()->product_id,
                'product_name' => $items->first()->product->name,
                'quantity' => $items->sum('quantity'),
                'revenue' => $items->sum(fn ($item) => $item->quantity * $item->product->price),
            ])
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        // Get or create report
        $report = DailySalesReport::firstOrCreate(
            ['report_date' => $reportDate],
            [
                'admin_id' => $admin->id,
                'total_items_sold' => $totalItemsSold,
                'total_revenue' => $totalRevenue,
                'unique_products_sold' => $uniqueProductsSold,
                'top_products' => $topProducts,
            ]
        );

        // If report already exists, update it
        if ($report->wasRecentlyCreated === false) {
            $report->update([
                'total_items_sold' => $totalItemsSold,
                'total_revenue' => $totalRevenue,
                'unique_products_sold' => $uniqueProductsSold,
                'top_products' => $topProducts,
            ]);
        }

        // Send email
        Mail::to($admin->email)->send(new DailySalesReportMail($report));

        // Mark as sent
        $report->markAsSent();

        \Illuminate\Support\Facades\Log::info('Daily sales report generated and sent', [
            'report_date' => $reportDate,
            'total_items_sold' => $totalItemsSold,
            'total_revenue' => $totalRevenue,
            'admin_id' => $admin->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('Daily sales report job failed', [
            'report_date' => $this->reportDate,
            'exception' => $exception->getMessage(),
        ]);
    }
}
