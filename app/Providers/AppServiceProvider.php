<?php

namespace App\Providers;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\Stores\SessionCartStore;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\MercadoPago\MercadoPagoCheckoutPreferenceGateway;
use App\Modules\Payments\MercadoPago\MercadoPagoPaymentDetailsGateway;
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
        $this->app->bind(CheckoutPreferenceGateway::class, MercadoPagoCheckoutPreferenceGateway::class);
        $this->app->bind(PaymentDetailsGateway::class, MercadoPagoPaymentDetailsGateway::class);
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

        RateLimiter::for('public-cart-mutations', fn (Request $request): Limit => Limit::perMinute(
            $this->positiveConfig('security.rate_limits.public_cart_mutations_per_minute', 60),
        )->by((string) $request->ip()));

        RateLimiter::for('public-order-creation', function (Request $request): array {
            return [
                Limit::perMinute($this->positiveConfig('security.rate_limits.public_order_creations_per_minute', 10))
                    ->by((string) $request->ip()),
                Limit::perMinute($this->positiveConfig('security.rate_limits.public_order_creations_per_session_minute', 3))
                    ->by($this->sessionRateLimitKey($request)),
            ];
        });

        RateLimiter::for('mercado-pago-webhooks', fn (Request $request): Limit => Limit::perMinute(
            $this->positiveConfig('security.rate_limits.mercado_pago_webhooks_per_minute', 60),
        )->by((string) $request->ip()));
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

    private function sessionRateLimitKey(Request $request): string
    {
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();

            if ($sessionId !== '') {
                return $sessionId;
            }
        }

        return (string) $request->ip();
    }

    private function positiveConfig(string $key, int $default): int
    {
        $value = (int) config($key, $default);

        return $value > 0 ? $value : $default;
    }
}
