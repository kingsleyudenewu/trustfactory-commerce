<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

class CartService
{
    /**
     * Get or create cart for a user
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );
    }

    /**
     * Get user's cart model with eager loaded items
     */
    public function getUserCart(User $user): Cart
    {
        return $this->getOrCreateCart($user)->load('items.product');
    }

    /**
     * Add item to cart
     */
    public function addItemToCart(User $user, int $productId, int $quantity = 1): CartItem
    {
        $cart = $this->getOrCreateCart($user);

        $existingItem = $cart->items()
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
            ]);

            return $existingItem;
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItemFromCart(User $user, int $cartItemId): bool
    {
        $cart = $this->getOrCreateCart($user);
        
        // Verify cart item belongs to this user's cart
        $cartItem = $cart->items()->where('id', $cartItemId)->first();
        
        if (!$cartItem) {
            throw new \Exception('Cart item not found or does not belong to this cart.');
        }

        return (bool) $cartItem->delete();
    }

    /**
     * Update item quantity in cart
     */
    public function updateItemQuantity(User $user, int $cartItemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItemFromCart($user, $cartItemId);
        }

        $cart = $this->getOrCreateCart($user);
        
        // Verify cart item belongs to this user's cart
        $cartItem = $cart->items()->where('id', $cartItemId)->first();
        
        if (!$cartItem) {
            throw new \Exception('Cart item not found or does not belong to this cart.');
        }

        return (bool) $cartItem->update(['quantity' => $quantity]);
    }

    /**
     * Clear all items from user's cart
     */
    public function clearCart(User $user): bool
    {
        $cart = $this->getOrCreateCart($user);

        return (bool) $cart->items()->delete();
    }

    /**
     * Get cart item count for a user
     */
    public function getCartItemCount(User $user): int
    {
        $cart = $this->getOrCreateCart($user);

        return $cart->items()->sum('quantity');
    }
}
