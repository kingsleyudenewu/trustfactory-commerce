<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->integer('total_items_sold')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->integer('unique_products_sold')->default(0);
            $table->json('top_products')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->text('report_content')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
            $table->index('report_date');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_reports');
    }
};
