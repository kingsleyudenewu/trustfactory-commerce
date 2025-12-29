<?php

namespace App\Actions\Cart;

use App\Models\User;
use App\Services\CartService;

class RemoveProductFromCart
{
    public function __construct(
        private CartService $cartService,
    ) {}

    /**
     * Remove a product from the user's cart
     *
     * @throws \Exception
     */
    public function execute(User $user, int $cartItemId): array
    {
        // Validate user is authenticated
        if (!$user || !$user->id) {
            throw new \Exception("User must be authenticated to remove items from cart.");
        }

        // CartService will validate ownership and throw exception if item not found
        $removed = $this->cartService->removeItemFromCart($user, $cartItemId);

        return [
            'success' => true,
            'message' => 'Product removed from cart.',
            'item_count' => $this->cartService->getCartItemCount($user),
        ];
    }
}
