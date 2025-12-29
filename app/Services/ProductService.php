<?php

namespace App\Services;

use App\Jobs\SendLowStockNotificationJob;
use App\Models\Product;
use Illuminate\Pagination\Paginator;

class ProductService
{
    /**
     * Get all products with pagination
     */
    public function getPaginatedProducts(int $perPage = 12): Paginator
    {
        return Product::paginate($perPage);
    }

    /**
     * Get a single product by ID
     */
    public function getProduct(int $productId): ?Product
    {
        return Product::find($productId);
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasStock(int $productId, int $quantity = 1): bool
    {
        $product = Product::find($productId);

        return $product && $product->stock_quantity >= $quantity;
    }

    /**
     * Get product stock quantity
     */
    public function getStockQuantity(int $productId): int
    {
        $product = Product::find($productId);

        return $product?->stock_quantity ?? 0;
    }

    /**
     * Check if product has low stock and dispatch notification if needed
     */
    public function checkAndNotifyLowStock(Product $product): void
    {
        $threshold = (int) config('app.low_stock_threshold', 5);

        if ($product->stock_quantity <= $threshold) {
            SendLowStockNotificationJob::dispatch($product, $product->stock_quantity, $threshold);
        }
    }
}
