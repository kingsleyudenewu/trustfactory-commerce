<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        // Create admin user
        User::firstOrCreate(
            ['email' => config('app.admin_email', 'admin@example.com')],
            ['name' => 'Admin User', 'password' => bcrypt('admin123')]
        );

        // Seed sample products
        $this->call(ProductSeeder::class);
    }
}
