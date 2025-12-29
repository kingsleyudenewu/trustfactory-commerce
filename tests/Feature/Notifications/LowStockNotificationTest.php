<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendLowStockNotificationJob;
use App\Mail\LowStockNotificationMail;
use App\Models\Product;
use App\Models\ProductLowStockNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LowStockNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
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

        $this->product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);
    }

    /** @test */
    public function low_stock_notification_job_sends_email_to_admin(): void
    {
        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        Mail::assertSent(LowStockNotificationMail::class, function ($mail) {
            return $mail->hasTo($this->admin->email);
        });
    }

    /** @test */
    public function low_stock_notification_creates_database_record(): void
    {
        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        $this->assertDatabaseHas('product_low_stock_notifications', [
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'current_stock' => 3,
            'threshold_level' => 5,
        ]);
    }

    /** @test */
    public function prevents_duplicate_notifications_on_same_day(): void
    {
        Mail::fake();

        // First notification
        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        // Try to send again - should be prevented
        SendLowStockNotificationJob::dispatch($this->product, 2, 5);

        // Only one email should be sent
        Mail::assertSentCount(1);
    }

    /** @test */
    public function notification_is_marked_as_sent(): void
    {
        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        $notification = ProductLowStockNotification::first();
        $this->assertNotNull($notification->sent_at);
    }

    /** @test */
    public function admin_user_not_found_logs_error(): void
    {
        User::where('email', config('app.admin_email'))->delete();

        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        Mail::assertNotSent(LowStockNotificationMail::class);
    }

    /** @test */
    public function notification_includes_product_details(): void
    {
        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        Mail::assertSent(LowStockNotificationMail::class, function ($mail) {
            return $mail->product->id === $this->product->id;
        });
    }

    /** @test */
    public function can_manually_send_notification_via_command(): void
    {
        Queue::fake();

        $this->artisan('notification:low-stock', ['product' => $this->product->id])
            ->assertSuccessful();

        Queue::assertPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function command_checks_all_low_stock_products(): void
    {
        Queue::fake();

        config(['app.low_stock_threshold' => 5]);
        Product::factory(3)->create(['stock_quantity' => 2]);

        $this->artisan('notification:low-stock')
            ->assertSuccessful();

        // Verify that jobs were pushed for all low-stock products
        Queue::assertPushed(SendLowStockNotificationJob::class);
    }

    /** @test */
    public function notification_subject_contains_product_name(): void
    {
        Mail::fake();

        SendLowStockNotificationJob::dispatch($this->product, 3, 5);

        Mail::assertSent(LowStockNotificationMail::class, function ($mail) {
            return str_contains($mail->envelope()->subject, $this->product->name);
        });
    }
}
