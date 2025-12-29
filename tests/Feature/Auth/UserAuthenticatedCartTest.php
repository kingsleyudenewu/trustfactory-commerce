<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Product;
use App\Services\CartService;
use App\Actions\Cart\AddProductToCart;
use App\Actions\Cart\RemoveProductFromCart;
use App\Actions\Cart\UpdateCartItemQuantity;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAuthenticatedCartTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected CartService $cartService;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartService = app(CartService::class);

        // Create test users
        $this->user = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->otherUser = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create test product
        $this->product = Product::factory()->create([
            'price' => 99.99,
            'stock_quantity' => 100,
        ]);
    }

    /**
     * Test: Unauthenticated users cannot access /products route
     */
    public function test_unauthenticated_users_redirected_from_products_route(): void
    {
        $response = $this->get('/products');
        $response->assertRedirect('/login');
    }

    /**
     * Test: Unauthenticated users cannot access /cart route
     */
    public function test_unauthenticated_users_redirected_from_cart_route(): void
    {
        $response = $this->get('/cart');
        $response->assertRedirect('/login');
    }

    /**
     * Test: User can add product to their cart
     */
    public function test_user_can_add_product_to_cart(): void
    {
        $action = app(AddProductToCart::class);
        $result = $action->execute($this->user, $this->product->id, 2);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['cart_item_id']);

        // Verify in database
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Test: Unauthenticated user cannot add product to cart
     */
    public function test_unauthenticated_user_cannot_add_to_cart(): void
    {
        $action = app(AddProductToCart::class);
        
        // Trying to pass null directly will cause TypeError, which is also correct behavior
        // The method signature requires User type, so null is invalid
        try {
            $action->execute(null, $this->product->id, 1);
            $this->fail('Expected exception not thrown');
        } catch (\TypeError $e) {
            // Type error is acceptable - user is required
            $this->assertStringContainsString('User', $e->getMessage());
        }
    }

    /**
     * Test: User can only add valid quantities
     */
    public function test_user_cannot_add_zero_quantity(): void
    {
        $action = app(AddProductToCart::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quantity must be at least 1');
        
        $action->execute($this->user, $this->product->id, 0);
    }

    /**
     * Test: User cannot add more than available stock
     */
    public function test_user_cannot_add_more_than_stock(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 5,
        ]);

        $action = app(AddProductToCart::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');
        
        $action->execute($this->user, $product->id, 10);
    }

    /**
     * Test: Each user has their own isolated cart
     */
    public function test_users_have_isolated_carts(): void
    {
        $action = app(AddProductToCart::class);

        // User 1 adds product
        $action->execute($this->user, $this->product->id, 2);

        // User 2 adds same product
        $action->execute($this->otherUser, $this->product->id, 5);

        // Verify each user has separate cart
        $user1Cart = $this->cartService->getUserCart($this->user);
        $user2Cart = $this->cartService->getUserCart($this->otherUser);

        $this->assertNotEquals($user1Cart->id, $user2Cart->id);
        $this->assertEquals(2, $user1Cart->items()->first()->quantity);
        $this->assertEquals(5, $user2Cart->items()->first()->quantity);
    }

    /**
     * Test: User cannot access another user's cart
     */
    public function test_user_cannot_view_another_user_cart(): void
    {
        // Add product to other user's cart
        $this->cartService->addItemToCart($this->otherUser, $this->product->id, 1);
        $otherUserCart = $this->cartService->getUserCart($this->otherUser);

        // Check authorization
        $canView = $this->user->can('view', $otherUserCart);
        
        $this->assertFalse($canView);
    }

    /**
     * Test: User can update quantity in their cart
     */
    public function test_user_can_update_cart_item_quantity(): void
    {
        // Add product to cart
        $cartItem = $this->cartService->addItemToCart($this->user, $this->product->id, 2);

        // Update quantity
        $action = app(UpdateCartItemQuantity::class);
        $result = $action->execute($this->user, $cartItem->id, 5);

        $this->assertTrue($result['success']);
        
        // Verify update
        $cartItem->refresh();
        $this->assertEquals(5, $cartItem->quantity);
    }

    /**
     * Test: User cannot update quantity for non-existent item
     */
    public function test_user_cannot_update_nonexistent_cart_item(): void
    {
        $action = app(UpdateCartItemQuantity::class);
        
        $this->expectException(\Exception::class);
        
        $action->execute($this->user, 99999, 5);
    }

    /**
     * Test: User can remove item from cart
     */
    public function test_user_can_remove_item_from_cart(): void
    {
        // Add product to cart
        $cartItem = $this->cartService->addItemToCart($this->user, $this->product->id, 2);

        // Remove item
        $action = app(RemoveProductFromCart::class);
        $result = $action->execute($this->user, $cartItem->id);

        $this->assertTrue($result['success']);
        
        // Verify removal
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Test: Service validates item belongs to user's cart
     */
    public function test_service_validates_item_ownership(): void
    {
        // Add product to user1's cart
        $cartItem = $this->cartService->addItemToCart($this->user, $this->product->id, 2);

        // Try to remove from user2's cart (should fail)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not belong to this cart');

        $this->cartService->removeItemFromCart($this->otherUser, $cartItem->id);
    }

    /**
     * Test: Cart persists across sessions
     */
    public function test_cart_persists_after_logout(): void
    {
        // Add product
        $this->cartService->addItemToCart($this->user, $this->product->id, 3);

        // Get cart
        $cart1 = $this->cartService->getUserCart($this->user);
        $cartItemCount1 = $cart1->items()->count();

        // Simulate logout/login
        auth()->logout();
        auth()->login($this->user);

        // Get cart again
        $cart2 = $this->cartService->getUserCart($this->user);
        $cartItemCount2 = $cart2->items()->count();

        // Verify cart data persists
        $this->assertEquals($cartItemCount1, $cartItemCount2);
        $this->assertEquals(3, $cart2->items()->first()->quantity);
    }

    /**
     * Test: User cannot bypass authorization with direct model access
     */
    public function test_policy_enforces_authorization(): void
    {
        // Create cart for user 1
        $this->cartService->addItemToCart($this->user, $this->product->id, 1);
        $user1Cart = $this->cartService->getUserCart($this->user);

        // Check if user 2 can update (should be false)
        $canUpdate = $this->otherUser->can('update', $user1Cart);
        
        $this->assertFalse($canUpdate);
    }

    /**
     * Test: Cart deleted when user is deleted
     */
    public function test_cart_deleted_when_user_deleted(): void
    {
        // Add product to cart
        $this->cartService->addItemToCart($this->user, $this->product->id, 1);
        $cart = $this->user->cart;
        $cartId = $cart->id;

        // Delete user
        $this->user->delete();

        // Verify cart is deleted
        $this->assertDatabaseMissing('carts', [
            'id' => $cartId,
        ]);
    }

    /**
     * Test: Multiple products in single cart
     */
    public function test_user_can_add_multiple_products_to_cart(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Add both products
        $this->cartService->addItemToCart($this->user, $product1->id, 2);
        $this->cartService->addItemToCart($this->user, $product2->id, 3);

        // Verify both in cart
        $cart = $this->cartService->getUserCart($this->user);
        $this->assertEquals(2, $cart->items()->count());
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);
    }

    /**
     * Test: Adding duplicate product increments quantity
     */
    public function test_adding_duplicate_product_increments_quantity(): void
    {
        // Add product once
        $this->cartService->addItemToCart($this->user, $this->product->id, 2);

        // Add same product again
        $this->cartService->addItemToCart($this->user, $this->product->id, 3);

        // Verify quantity was incremented
        $cart = $this->cartService->getUserCart($this->user);
        $cartItem = $cart->items()->first();
        $this->assertEquals(5, $cartItem->quantity);
    }

    /**
     * Test: Cart item count is accurate (returns total quantity)
     */
    public function test_cart_item_count_is_accurate(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Add products
        $this->cartService->addItemToCart($this->user, $product1->id, 2);
        $this->cartService->addItemToCart($this->user, $product2->id, 3);

        // Check count - getCartItemCount returns total quantity
        $itemCount = $this->cartService->getCartItemCount($this->user);
        $this->assertEquals(5, $itemCount); // 2 + 3 = 5 total quantity
    }
}
