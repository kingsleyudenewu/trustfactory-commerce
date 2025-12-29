<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create products with normal stock levels
        Product::factory(20)->create();

        // Create some low-stock products for notification testing
        Product::factory(4)->create([
            'stock_quantity' => fake()->numberBetween(1, 5),
        ]);
    }
}
