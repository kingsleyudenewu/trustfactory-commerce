<?php

namespace App\Actions\Cart;

use App\Models\User;
use App\Services\CartService;
use App\Services\ProductService;

class AddProductToCart
{
    public function __construct(
        private CartService $cartService,
        private ProductService $productService,
    ) {}

    /**
     * Add a product to the user's cart
     *
     * @throws \Exception
     */
    public function execute(User $user, int $productId, int $quantity = 1): array
    {
        // Validate user is authenticated
        if (!$user || !$user->id) {
            throw new \Exception("User must be authenticated to add items to cart.");
        }

        // Validate product exists
        if (! $this->productService->getProduct($productId)) {
            throw new \Exception("Product not found.");
        }

        // Validate quantity is positive
        if ($quantity < 1) {
            throw new \Exception("Quantity must be at least 1.");
        }

        // Validate stock availability
        if (! $this->productService->hasStock($productId, $quantity)) {
            throw new \Exception("Insufficient stock for this product.");
        }

        // Add to cart
        $cartItem = $this->cartService->addItemToCart($user, $productId, $quantity);

        return [
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'cart_item_id' => $cartItem->id,
            'item_count' => $this->cartService->getCartItemCount($user),
        ];
    }
}
