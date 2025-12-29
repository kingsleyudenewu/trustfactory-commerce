<?php

namespace App\Jobs;

use App\Mail\LowStockNotificationMail;
use App\Models\Product;
use App\Models\ProductLowStockNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public function __construct(
        public Product $product,
        public int $currentStock,
        public int $threshold = 5,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get admin user
        $admin = User::where('email', config('app.admin_email'))->first();
        
        if (!$admin) {
            // Log error if admin not found
            \Illuminate\Support\Facades\Log::error('Admin user not found for low stock notification', [
                'product_id' => $this->product->id,
                'admin_email' => config('app.admin_email'),
            ]);
            return;
        }

        // Check if already notified today
        if (ProductLowStockNotification::sentTodayForProduct($this->product->id)) {
            return;
        }

        // Create notification record
        $notification = ProductLowStockNotification::create([
            'product_id' => $this->product->id,
            'admin_id' => $admin->id,
            'current_stock' => $this->currentStock,
            'threshold_level' => $this->threshold,
            'notification_type' => 'low_stock',
        ]);

        // Send email
        Mail::to($admin->email)->send(new LowStockNotificationMail(
            $this->product,
            $this->currentStock,
            $this->threshold,
        ));

        // Mark as sent
        $notification->markAsSent();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('Low stock notification job failed', [
            'product_id' => $this->product->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
