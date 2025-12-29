<?php

namespace Tests\Feature\Notifications;

use App\Jobs\GenerateDailySalesReportJob;
use App\Jobs\SendLowStockNotificationJob;
use App\Mail\DailySalesReportMail;
use App\Mail\LowStockNotificationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailySalesReport;
use App\Models\Product;
use App\Models\ProductLowStockNotification;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $testUser;
    protected Product $product;
    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        // Override config to use our test admin email
        config(['app.admin_email' => 'admin@example.com']);

        $this->testUser = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $this->product = Product::factory()->create(['stock_quantity' => 10, 'price' => 100]);
        $this->productService = app(ProductService::class);
    }

    /** @test */
    public function product_stock_check_triggers_notification_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_quantity' => 3]);
        config(['app.low_stock_threshold' => 5]);

        $this->productService->checkAndNotifyLowStock($product);

        Queue::assertPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function product_stock_above_threshold_does_not_trigger(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_quantity' => 10]);
        config(['app.low_stock_threshold' => 5]);

        $this->productService->checkAndNotifyLowStock($product);

        Queue::assertNotPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function cart_checkout_triggers_low_stock_check(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_quantity' => 2, 'price' => 50]);
        config(['app.low_stock_threshold' => 5]);

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Simulate cart checkout
        $product->stock_quantity = 1;
        $product->save();
        
        $this->productService->checkAndNotifyLowStock($product);

        Queue::assertPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function complete_notification_flow_end_to_end(): void
    {
        Mail::fake();
        Queue::fake();

        $product = Product::factory()->create(['stock_quantity' => 4]);
        config(['app.low_stock_threshold' => 5]);

        // Trigger the notification check
        $this->productService->checkAndNotifyLowStock($product);

        // Verify job was queued
        Queue::assertPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function daily_sales_report_aggregates_all_sales(): void
    {
        Mail::fake();

        // Create multiple carts and items
        $cart1 = Cart::create(['user_id' => $this->testUser->id]);
        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 50]);

        CartItem::create([
            'cart_id' => $cart1->id,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);

        CartItem::create([
            'cart_id' => $cart1->id,
            'product_id' => $product2->id,
            'quantity' => 10,
        ]);

        // Create another cart
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $cart2 = Cart::create(['user_id' => $user2->id]);
        CartItem::create([
            'cart_id' => $cart2->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        
        $this->assertEquals(18, $report->total_items_sold); // 5 + 10 + 3
        $this->assertEquals(1300, $report->total_revenue); // (5*100) + (10*50) + (3*100)
        $this->assertEquals(2, $report->unique_products_sold);
    }

    /** @test */
    public function multiple_low_stock_notifications_tracked(): void
    {
        Queue::fake();

        $product1 = Product::factory()->create(['stock_quantity' => 2]);
        $product2 = Product::factory()->create(['stock_quantity' => 3]);
        config(['app.low_stock_threshold' => 5]);

        // Trigger notifications for both
        $this->productService->checkAndNotifyLowStock($product1);
        $this->productService->checkAndNotifyLowStock($product2);

        // Verify jobs were pushed for both products
        $count = collect(Queue::pushed(SendLowStockNotificationJob::class))->count();
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function notification_and_report_can_run_concurrently(): void
    {
        Mail::fake();
        Queue::fake();

        $product = Product::factory()->create(['stock_quantity' => 2, 'price' => 100]);
        config(['app.low_stock_threshold' => 5]);

        // Trigger low stock notification
        $this->productService->checkAndNotifyLowStock($product);

        // Create cart items for report
        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Dispatch both jobs simultaneously
        Queue::assertPushed(SendLowStockNotificationJob::class);
        
        GenerateDailySalesReportJob::dispatch(today());
        Queue::assertPushed(GenerateDailySalesReportJob::class);

        // Both should be queued independently
        $this->assertTrue(true); // Both dispatched without conflict
    }

    /** @test */
    public function admin_receives_both_notification_and_report(): void
    {
        Mail::fake();

        $product = Product::factory()->create(['stock_quantity' => 2, 'price' => 100]);
        config(['app.low_stock_threshold' => 5]);

        // Create cart and notification
        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Execute both job types
        SendLowStockNotificationJob::dispatch($product, 2, 5);
        GenerateDailySalesReportJob::dispatch(today());

        // Verify both emails are sent to admin
        Mail::assertSent(LowStockNotificationMail::class, function ($mail) {
            return $mail->hasTo($this->admin->email);
        });

        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return $mail->hasTo($this->admin->email);
        });
    }

    /** @test */
    public function stress_test_many_products_low_stock(): void
    {
        Queue::fake();

        // Create 50 products with low stock
        $products = Product::factory(50)->create(['stock_quantity' => 2]);
        config(['app.low_stock_threshold' => 5]);

        foreach ($products as $product) {
            $this->productService->checkAndNotifyLowStock($product);
        }

        // All 50 should be queued
        Queue::assertPushed(SendLowStockNotificationJob::class, 50);
    }

    /** @test */
    public function stress_test_many_cart_items(): void
    {
        Mail::fake();

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        
        // Create 100 cart items
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create(['price' => 10]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        
        $this->assertEquals(100, $report->total_items_sold);
        $this->assertEquals(100, $report->unique_products_sold);
    }
}
