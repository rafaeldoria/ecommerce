<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use App\Modules\Payments\MercadoPago\MercadoPagoCheckoutUrlResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoCheckoutUrlResolverTest extends TestCase
{
    #[Test]
    public function it_returns_allowed_mercado_pago_checkout_urls(): void
    {
        config([
            'services.mercado_pago.checkout_allowed_hosts' => ['www.mercadopago.com.br'],
        ]);

        $url = app(MercadoPagoCheckoutUrlResolver::class)->resolve((object) [
            'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=123',
        ]);

        $this->assertSame('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=123', $url);
    }

    #[Test]
    public function it_rejects_untrusted_checkout_url_hosts(): void
    {
        config([
            'services.mercado_pago.checkout_allowed_hosts' => ['www.mercadopago.com.br'],
        ]);

        $this->expectException(PaymentConfigurationMissing::class);

        app(MercadoPagoCheckoutUrlResolver::class)->resolve((object) [
            'init_point' => 'https://payments.example.test/checkout',
        ]);
    }
}
