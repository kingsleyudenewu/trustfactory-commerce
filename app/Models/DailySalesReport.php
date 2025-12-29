<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySalesReport extends Model
{
    protected $fillable = [
        'report_date',
        'total_items_sold',
        'total_revenue',
        'unique_products_sold',
        'top_products',
        'admin_id',
        'sent_at',
        'report_content',
        'is_sent',
    ];

    protected $casts = [
        'report_date' => 'date',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'top_products' => 'collection',
    ];

    /**
     * Get the admin user associated with this report
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get or create report for today
     */
    public static function forToday()
    {
        return self::firstOrCreate(['report_date' => today()]);
    }

    /**
     * Get all unsent reports
     */
    public static function unsent()
    {
        return self::where('is_sent', false)->whereNull('sent_at');
    }

    /**
     * Mark report as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now(),
            'is_sent' => true,
        ]);
    }
}
