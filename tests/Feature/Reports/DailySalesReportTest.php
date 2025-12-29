<?php

namespace Tests\Feature\Reports;

use App\Jobs\GenerateDailySalesReportJob;
use App\Mail\DailySalesReportMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailySalesReport;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DailySalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $testUser;
    protected Product $product;

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
        $this->product = Product::factory()->create(['price' => 100]);
    }

    /** @test */
    public function daily_sales_report_job_generates_report(): void
    {
        Mail::fake();

        // Create cart items for today
        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $this->assertDatabaseHas('daily_sales_reports', [
            'report_date' => today(),
            'total_items_sold' => 5,
            'total_revenue' => 500,
            'unique_products_sold' => 1,
        ]);
    }

    /** @test */
    public function daily_sales_report_sends_email_to_admin(): void
    {
        Mail::fake();

        // Create cart items
        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return $mail->hasTo($this->admin->email);
        });
    }

    /** @test */
    public function report_is_marked_as_sent(): void
    {
        Mail::fake();

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        $this->assertEquals(1, $report->is_sent); // SQLite stores booleans as 0 or 1
        $this->assertNotNull($report->sent_at);
    }

    /** @test */
    public function report_calculates_correct_metrics(): void
    {
        Mail::fake();

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        
        // Add multiple products
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        $product2 = Product::factory()->create(['price' => 50]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        
        $this->assertEquals(8, $report->total_items_sold); // 5 + 3
        $this->assertEquals(650, $report->total_revenue); // (5 * 100) + (3 * 50)
        $this->assertEquals(2, $report->unique_products_sold);
    }

    /** @test */
    public function report_includes_top_products(): void
    {
        Mail::fake();

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        
        $this->assertNotNull($report->top_products);
        $this->assertEquals(1, $report->top_products->count());
        $this->assertEquals($this->product->id, $report->top_products[0]['product_id']);
    }

    /** @test */
    public function report_with_no_sales_still_created(): void
    {
        Mail::fake();

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        
        $this->assertNotNull($report);
        $this->assertEquals(0, $report->total_items_sold);
        $this->assertEquals(0, $report->total_revenue);
    }

    /** @test */
    public function can_manually_generate_report_via_command(): void
    {
        Queue::fake();

        $this->artisan('report:daily-sales')
            ->assertSuccessful();

        Queue::assertPushed(GenerateDailySalesReportJob::class);
    }

    /** @test */
    public function command_accepts_custom_date(): void
    {
        Queue::fake();

        $this->artisan('report:daily-sales', ['date' => '2025-12-25'])
            ->assertSuccessful();

        Queue::assertPushed(GenerateDailySalesReportJob::class);
    }

    /** @test */
    public function report_email_contains_date_range(): void
    {
        Mail::fake();

        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return str_contains($mail->envelope()->subject, today()->format('F j, Y'));
        });
    }

    /** @test */
    public function report_only_includes_todays_sales(): void
    {
        Mail::fake();

        // Create yesterday's cart items
        $yesterdayCart = Cart::create(['user_id' => $this->testUser->id]);
        $yesterdayItem = CartItem::create([
            'cart_id' => $yesterdayCart->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);
        $yesterdayItem->created_at = now()->subDay();
        $yesterdayItem->save();

        // Create today's cart items
        $todayCart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $todayCart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        $this->assertEquals(5, $report->total_items_sold); // Only today's sales
    }

    /** @test */
    public function updating_existing_report_recalculates_metrics(): void
    {
        Mail::fake();

        // Create initial report
        DailySalesReport::create([
            'report_date' => today(),
            'total_items_sold' => 10,
            'total_revenue' => 1000,
            'unique_products_sold' => 1,
        ]);

        // Create new cart items
        $cart = Cart::create(['user_id' => $this->testUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        // Run report generation
        GenerateDailySalesReportJob::dispatch(today());

        $report = DailySalesReport::where('report_date', today())->first();
        $this->assertEquals(5, $report->total_items_sold); // Updated, not summed
    }
}
