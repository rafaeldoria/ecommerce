<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use App\Modules\Payments\MercadoPago\MercadoPagoCheckoutPreferenceGateway;
use App\Modules\Payments\MercadoPago\MercadoPagoPreferenceClient;
use MercadoPago\MercadoPagoConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoCheckoutEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
    }

    #[Test]
    public function it_uses_test_credentials_mode_and_init_point_by_default(): void
    {
        $client = $this->fakePreferenceClient();
        $this->app->instance(MercadoPagoPreferenceClient::class, $client);

        config([
            'services.mercado_pago.access_token' => 'TEST-access-token',
            'services.mercado_pago.public_key' => 'TEST-public-key',
            'services.mercado_pago.credential_mode' => 'test',
            'services.mercado_pago.checkout_url_strategy' => 'init_point',
        ]);

        $result = app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());

        $this->assertSame('pref_test_123', $result->preferenceId);
        $this->assertSame('TEST-public-key', $result->publicKey);
        $this->assertSame('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=123', $result->checkoutUrl);
        $this->assertSame(MercadoPagoConfig::LOCAL, MercadoPagoConfig::getRuntimeEnviroment());
        $this->assertSame('buyer@example.com', $client->request['payer']['email']);
    }

    #[Test]
    public function it_can_explicitly_select_sandbox_init_point_for_environment_proof(): void
    {
        $this->app->instance(MercadoPagoPreferenceClient::class, $this->fakePreferenceClient());

        config([
            'services.mercado_pago.access_token' => 'TEST-access-token',
            'services.mercado_pago.public_key' => 'TEST-public-key',
            'services.mercado_pago.credential_mode' => 'test',
            'services.mercado_pago.checkout_url_strategy' => 'sandbox_init_point',
        ]);

        $result = app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());

        $this->assertSame('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=123', $result->checkoutUrl);
        $this->assertSame(MercadoPagoConfig::LOCAL, MercadoPagoConfig::getRuntimeEnviroment());
    }

    #[Test]
    public function it_uses_server_runtime_for_production_credential_mode(): void
    {
        $this->app->instance(MercadoPagoPreferenceClient::class, $this->fakePreferenceClient());

        config([
            'services.mercado_pago.access_token' => 'APP_USR-access-token',
            'services.mercado_pago.public_key' => 'APP_USR-public-key',
            'services.mercado_pago.credential_mode' => 'production',
            'services.mercado_pago.checkout_url_strategy' => 'init_point',
        ]);

        app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());

        $this->assertSame(MercadoPagoConfig::SERVER, MercadoPagoConfig::getRuntimeEnviroment());
    }

    #[Test]
    public function it_rejects_invalid_credential_mode_values(): void
    {
        $this->app->instance(MercadoPagoPreferenceClient::class, $this->fakePreferenceClient());

        config([
            'services.mercado_pago.access_token' => 'TEST-access-token',
            'services.mercado_pago.public_key' => 'TEST-public-key',
            'services.mercado_pago.credential_mode' => 'sandbox',
        ]);

        $this->expectException(PaymentConfigurationMissing::class);

        app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());
    }

    #[Test]
    public function it_rejects_invalid_checkout_url_strategy_values(): void
    {
        $this->app->instance(MercadoPagoPreferenceClient::class, $this->fakePreferenceClient());

        config([
            'services.mercado_pago.access_token' => 'TEST-access-token',
            'services.mercado_pago.public_key' => 'TEST-public-key',
            'services.mercado_pago.credential_mode' => 'test',
            'services.mercado_pago.checkout_url_strategy' => 'wallet_only',
        ]);

        $this->expectException(PaymentConfigurationMissing::class);

        app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());
    }

    #[Test]
    public function it_rejects_missing_or_blank_credentials(): void
    {
        $this->app->instance(MercadoPagoPreferenceClient::class, $this->fakePreferenceClient());

        config([
            'services.mercado_pago.access_token' => '   ',
            'services.mercado_pago.public_key' => '',
            'services.mercado_pago.credential_mode' => 'test',
            'services.mercado_pago.checkout_url_strategy' => 'init_point',
        ]);

        $this->expectException(PaymentConfigurationMissing::class);

        app(MercadoPagoCheckoutPreferenceGateway::class)->create($this->preferenceData());
    }

    private function fakePreferenceClient(): MercadoPagoPreferenceClient
    {
        return new class extends MercadoPagoPreferenceClient
        {
            public array $request = [];

            public function create(array $request): object
            {
                $this->request = $request;

                return (object) [
                    'id' => 'pref_test_123',
                    'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=123',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=123',
                ];
            }
        };
    }

    private function preferenceData(): CheckoutPreferenceData
    {
        return new CheckoutPreferenceData(
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
        );
    }
}
