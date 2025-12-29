<div class="space-y-6">
    <!-- Success Message -->
    @if ($successMessage)
        <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
            <div class="font-medium">{{ $successMessage }}</div>
        </div>
    @endif

    <!-- Error Message -->
    @if ($errorMessage)
        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
            <div class="font-medium">{{ $errorMessage }}</div>
        </div>
    @endif

    <!-- Products Grid -->
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($products as $product)
            <div class="flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                <!-- Product Image Placeholder -->
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200"></div>

                <!-- Product Info -->
                <div class="flex flex-1 flex-col p-4">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $product->name }}</h3>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ $product->description ?? 'No description available' }}
                    </p>

                    <!-- Price and Stock -->
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-gray-900">
                            ${{ number_format($product->price, 2) }}
                        </span>
                        <span class="text-xs text-gray-500">
                            Stock: {{ $product->stock_quantity }}
                        </span>
                    </div>

                    <!-- Add to Cart Form -->
                    <form wire:submit="addToCart({{ $product->id }})" class="mt-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <label for="quantity-{{ $product->id }}" class="text-xs font-medium text-gray-700">
                                Qty:
                            </label>
                            <select
                                id="quantity-{{ $product->id }}"
                                wire:model="selectedQuantity"
                                class="rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none"
                            >
                                @for ($i = 1; $i <= min(10, $product->stock_quantity); $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <button
                            type="submit"
                            {{ $product->stock_quantity === 0 ? 'disabled' : '' }}
                            class="w-full rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                {{ $product->stock_quantity === 0
                                    ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                                    : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                        >
                            {{ $product->stock_quantity === 0 ? 'Out of Stock' : 'Add to Cart' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-500">No products available at the moment.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
