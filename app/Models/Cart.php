<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * Get the user that owns this cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get total number of items in cart.
     */
    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate total cart value.
     */
    public function getTotal(): float
    {
        return $this->items->sum(fn (CartItem $item) => $item->getSubtotal());
    }

    /**
     * Get formatted total for display.
     */
    public function getFormattedTotal(): string
    {
        return '$' . number_format($this->getTotal(), 2);
    }

    /**
     * Check if cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
