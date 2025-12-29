<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartPolicy
{
    /**
     * Determine whether the user can view the cart.
     * Only the owner can view their own cart.
     */
    public function view(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }

    /**
     * Determine whether the user can update the cart.
     * Only the owner can update their own cart.
     */
    public function update(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }

    /**
     * Determine whether the user can delete the cart.
     * Only the owner can delete their own cart.
     */
    public function delete(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }

    /**
     * Determine whether the user can manage cart items.
     * Only the owner can manage items in their own cart.
     */
    public function manageItems(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }

    /**
     * Determine whether the user can restore the cart.
     */
    public function restore(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }

    /**
     * Determine whether the user can permanently delete the cart.
     */
    public function forceDelete(User $user, Cart $cart): bool
    {
        return $user->id === $cart->user_id;
    }
}
