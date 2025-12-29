<?php

namespace App\Livewire;

use App\Actions\Cart\AddProductToCart;
use App\Models\Product;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalog extends Component
{
    use WithPagination;

    public ?int $userId = null;

    #[Validate('required|integer|min:1')]
    public int $selectedQuantity = 1;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        // Capture authenticated user ID on component mount
        $user = auth()->user();
        if ($user) {
            $this->userId = $user->id;
        }
    }

    public function addToCart(int $productId): void
    {
        $this->clearMessages();

        try {
            // Ensure user is authenticated
            $user = auth()->user();
            if (!$user) {
                $this->errorMessage = 'You must be logged in to add items to cart.';
                return;
            }

            $action = app(AddProductToCart::class);
            $result = $action->execute(
                $user,
                $productId,
                $this->selectedQuantity
            );

            $this->successMessage = $result['message'];
            $this->selectedQuantity = 1;

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
        return view('livewire.product-catalog', [
            'products' => Product::paginate(12),
        ]);
    }
}
