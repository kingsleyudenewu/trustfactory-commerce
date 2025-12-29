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

    @if ($cart->isEmpty())
        <!-- Empty Cart Message -->
        <div class="rounded-lg border border-gray-200 bg-white p-12 text-center">
            <p class="mb-4 text-gray-500">Your cart is empty</p>
            <a href="{{ route('products') }}" class="inline-block rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Continue Shopping
            </a>
        </div>
    @else
        <div class="space-y-6">
            <!-- Cart Items Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-900">Price</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-900">Quantity</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-900">Subtotal</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-900"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($cart->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-700">
                                    {{ $item->getFormattedPrice() }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                            class="rounded border border-gray-300 p-1 hover:bg-gray-100"
                                            title="Decrease quantity"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <input
                                            type="number"
                                            wire:blur="updateQuantity({{ $item->id }}, $event.target.value)"
                                            value="{{ $item->quantity }}"
                                            min="1"
                                            class="w-12 rounded border border-gray-300 text-center text-sm"
                                        />
                                        <button
                                            wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                            class="rounded border border-gray-300 p-1 hover:bg-gray-100"
                                            title="Increase quantity"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                    {{ $item->getFormattedSubtotal() }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        wire:click="removeItem({{ $item->id }})"
                                        class="rounded text-red-600 hover:bg-red-50 p-2"
                                        title="Remove from cart"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cart Summary -->
            <div class="flex justify-end">
                <div class="w-full max-w-sm rounded-lg border border-gray-200 bg-white p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items:</span>
                            <span class="font-medium">{{ $cart->getItemCount() }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between">
                                <span class="text-lg font-semibold">Total:</span>
                                <span class="text-lg font-bold text-gray-900">{{ $cart->getFormattedTotal() }}</span>
                            </div>
                        </div>
                        <button class="w-full rounded-lg bg-green-600 px-4 py-2 font-medium text-white hover:bg-green-700">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
