<?php

namespace App\Providers;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\Stores\SessionCartStore;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
            $identifier = $request->input('username', $request->input('email'));

            if (!is_string($identifier) || trim($identifier) === '') {
                return Limit::perMinute(5)->by($request->ip());
            }

            return Limit::perMinute(5)->by(Str::lower(trim($identifier)));
        });
    }
}
