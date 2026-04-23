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
            $identifier = $this->adminLoginIdentifier($request);

            if ($identifier === null) {
                return Limit::perMinute(5)->by($request->ip());
            }

            return Limit::perMinute(5)->by($identifier);
        });
    }

    private function adminLoginIdentifier(Request $request): ?string
    {
        foreach (['username', 'email'] as $key) {
            $identifier = $request->input($key);

            if (!is_string($identifier)) {
                continue;
            }

            $identifier = trim($identifier);

            if ($identifier !== '') {
                return Str::lower($identifier);
            }
        }

        return null;
    }
}
