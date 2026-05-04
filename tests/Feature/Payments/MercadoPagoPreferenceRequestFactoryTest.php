<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\MercadoPago\MercadoPagoPreferenceRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoPreferenceRequestFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_the_checkout_pro_preference_request(): void
    {
        config(['services.mercado_pago.statement_descriptor' => 'GRSHOP']);

        $request = app(MercadoPagoPreferenceRequestFactory::class)->create(new CheckoutPreferenceData(
            email: 'buyer@example.com',
            externalReference: 'payment-external-reference-123',
            items: [
                [
                    'id' => '10',
                    'title' => 'Dota 2 Item',
                    'quantity' => 1,
                    'unit_price' => 25.9,
                    'currency_id' => 'BRL',
                ],
            ],
            backUrls: [
                'success' => 'https://store.test/success',
                'failure' => 'https://store.test/failure',
                'pending' => 'https://store.test/pending',
            ],
        ));

        $this->assertSame('buyer@example.com', $request['payer']['email']);
        $this->assertSame('payment-external-reference-123', $request['external_reference']);
        $this->assertSame('GRSHOP', $request['statement_descriptor']);
        $this->assertSame('approved', $request['auto_return']);
        $this->assertFalse($request['expires']);
        $this->assertSame('https://store.test/success', $request['back_urls']['success']);
        $this->assertSame(25.9, $request['items'][0]['unit_price']);
    }

    #[Test]
    public function it_omits_auto_return_for_localhost_back_urls(): void
    {
        $request = app(MercadoPagoPreferenceRequestFactory::class)->create(new CheckoutPreferenceData(
            email: 'buyer@example.com',
            externalReference: 'payment-external-reference-123',
            items: [
                [
                    'id' => '10',
                    'title' => 'Dota 2 Item',
                    'quantity' => 1,
                    'unit_price' => 25.9,
                    'currency_id' => 'BRL',
                ],
            ],
            backUrls: [
                'success' => 'http://localhost:8080/checkout/mercado-pago/success',
                'failure' => 'http://localhost:8080/checkout/mercado-pago/failure',
                'pending' => 'http://localhost:8080/checkout/mercado-pago/pending',
            ],
        ));

        $this->assertArrayNotHasKey('auto_return', $request);
    }
}
