<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    /**
     * Get the cart that owns this item.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product name (via relationship).
     */
    public function getProductNameAttribute(): string
    {
        return $this->product->name;
    }

    /**
     * Calculate subtotal for this item.
     */
    public function getSubtotal(): float
    {
        return (float) $this->product->price * $this->quantity;
    }

    /**
     * Get formatted price for display.
     */
    public function getFormattedPrice(): string
    {
        return $this->product->getFormattedPrice();
    }

    /**
     * Get formatted subtotal for display.
     */
    public function getFormattedSubtotal(): string
    {
        return '$' . number_format($this->getSubtotal(), 2);
    }
}
