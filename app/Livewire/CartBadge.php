<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $itemCount = 0;
    public ?int $userId = null;

    public function mount(CartService $cartService): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            $this->userId = $user->id;
            $this->itemCount = $cartService->getCartItemCount($user);
        }
    }

    #[On('cartUpdated')]
    public function updateCount(int $itemCount): void
    {
        // Verify user is still authenticated
        if (!auth()->check() || auth()->user()->id !== $this->userId) {
            $this->itemCount = 0;
            return;
        }
        
        $this->itemCount = $itemCount;
    }

    public function render()
    {
        return view('livewire.cart-badge');
    }
}
