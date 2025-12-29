<?php

namespace App\Actions\Cart;

use App\Models\User;
use App\Services\CartService;
use App\Services\ProductService;

class UpdateCartItemQuantity
{
    public function __construct(
        private CartService $cartService,
        private ProductService $productService,
    ) {}

    /**
     * Update quantity of a product in the user's cart
     *
     * @throws \Exception
     */
    public function execute(User $user, int $cartItemId, int $quantity): array
    {
        // Validate user is authenticated
        if (!$user || !$user->id) {
            throw new \Exception("User must be authenticated to update cart items.");
        }

        // If quantity is 0 or negative, remove the item
        if ($quantity <= 0) {
            return (new RemoveProductFromCart($this->cartService))
                ->execute($user, $cartItemId);
        }

        // Validate quantity is reasonable
        if ($quantity > 9999) {
            throw new \Exception("Quantity cannot exceed 9999.");
        }

        $cart = $this->cartService->getUserCart($user);
        $cartItem = $cart->items()->find($cartItemId);

        if (! $cartItem) {
            throw new \Exception("Cart item not found or does not belong to your cart.");
        }

        // Validate stock availability
        $quantityDifference = $quantity - $cartItem->quantity;
        if ($quantityDifference > 0) {
            if (! $this->productService->hasStock($cartItem->product_id, $quantityDifference)) {
                throw new \Exception("Insufficient stock available. Only " . $this->productService->getStockQuantity($cartItem->product_id) . " items in stock.");
            }
        }

        // Update quantity
        $this->cartService->updateItemQuantity($user, $cartItemId, $quantity);

        return [
            'success' => true,
            'message' => 'Cart item quantity updated.',
            'item_count' => $this->cartService->getCartItemCount($user),
        ];
    }
}
