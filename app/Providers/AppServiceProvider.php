<?php

namespace App\Providers;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\Stores\SessionCartStore;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CartStore::class, SessionCartStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->input('username', $request->input('email', $request->ip())));
        });
    }
}
