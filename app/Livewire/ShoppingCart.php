<?php

namespace App\Livewire;

use App\Actions\Cart\RemoveProductFromCart;
use App\Actions\Cart\UpdateCartItemQuantity;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class ShoppingCart extends Component
{
    public ?int $userId = null;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        // Capture and validate authenticated user
        $user = auth()->user();
        if (!$user) {
            throw new \Exception('Unauthorized access to shopping cart.');
        }

        $this->userId = $user->id;
    }

    #[On('cartUpdated')]
    public function onCartUpdated(): void
    {
        // Livewire will automatically re-render
    }

    public function updateQuantity(int $cartItemId, int $quantity): void
    {
        $this->clearMessages();

        try {
            $user = auth()->user();
            if (!$user || $user->id !== $this->userId) {
                throw new \Exception('Unauthorized cart operation.');
            }

            $action = app(UpdateCartItemQuantity::class);
            $result = $action->execute(
                $user,
                $cartItemId,
                $quantity
            );

            $this->successMessage = $result['message'];
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removeItem(int $cartItemId): void
    {
        $this->clearMessages();

        try {
            $user = auth()->user();
            if (!$user || $user->id !== $this->userId) {
                throw new \Exception('Unauthorized cart operation.');
            }

            $action = app(RemoveProductFromCart::class);
            $result = $action->execute(
                $user,
                $cartItemId
            );

            $this->successMessage = $result['message'];
            $this->dispatch('cartUpdated', itemCount: $result['item_count']);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        $cart = app(CartService::class)->getUserCart(auth()->user());

        return view('livewire.shopping-cart', ['cart' => $cart]);
    }
}
