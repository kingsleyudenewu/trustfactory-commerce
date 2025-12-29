<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLowStockNotification extends Model
{
    protected $fillable = [
        'product_id',
        'admin_id',
        'current_stock',
        'threshold_level',
        'sent_at',
        'notification_type',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product associated with this notification
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin user associated with this notification
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Check if notification was sent today for this product
     */
    public static function sentTodayForProduct(int $productId): bool
    {
        return self::where('product_id', $productId)
            ->whereDate('sent_at', today())
            ->exists();
    }

    /**
     * Get all unsent notifications
     */
    public static function unsent()
    {
        return self::whereNull('sent_at');
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }
}
