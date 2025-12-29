<?php

namespace App\Providers;

use App\Models\Cart;
use App\Policies\CartPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();
    }

    /**
     * Register model policies
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(Cart::class, CartPolicy::class);
    }
}
