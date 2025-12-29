<?php

namespace App\Console\Commands;

use App\Jobs\SendLowStockNotificationJob;
use App\Models\Product;
use Illuminate\Console\Command;

class SendLowStockNotificationCommand extends Command
{
    protected $signature = 'notification:low-stock {product? : Product ID to send notification for}';

    protected $description = 'Send low stock notification for a specific product or check all products';

    public function handle(): int
    {
        $productId = $this->argument('product');
        $threshold = (int) config('app.low_stock_threshold', 5);

        if ($productId) {
            // Send for specific product
            $product = Product::find($productId);
            
            if (!$product) {
                $this->error("Product with ID {$productId} not found.");
                return self::FAILURE;
            }

            if ($product->stock_quantity <= $threshold) {
                $this->info("Dispatching low stock notification for: {$product->name}");
                SendLowStockNotificationJob::dispatch($product, $product->stock_quantity, $threshold);
                $this->info("✓ Notification job dispatched");
                return self::SUCCESS;
            } else {
                $this->warn("Product '{$product->name}' has sufficient stock ({$product->stock_quantity} units)");
                return self::SUCCESS;
            }
        }

        // Check all products
        $this->info("Checking all products for low stock (threshold: {$threshold} units)...\n");

        $query = Product::where('stock_quantity', '<=', $threshold);
        $count = $query->count();

        if ($count === 0) {
            $this->info("✓ All products have sufficient stock");
            return self::SUCCESS;
        }

        $this->warn("Found {$count} products with low stock:\n");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($query->cursor() as $product) {
            SendLowStockNotificationJob::dispatch($product, $product->stock_quantity, $threshold);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Dispatched {$count} notification jobs");

        return self::SUCCESS;
    }
}
