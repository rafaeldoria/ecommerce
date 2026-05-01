<?php

namespace Tests\Feature\Payments;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\CreateCheckoutPreferenceAction;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\CreateCheckoutPreferenceData;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutPreferenceActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_mercado_pago_preference_payload_from_the_current_cart_without_creating_an_order(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public ?CheckoutPreferenceData $data = null;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->data = $data;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create([
            'name' => 'Phantom Assassin Arcana',
            'price' => 139900,
            'quantity' => 5,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 2,
        ));

        $result = app(CreateCheckoutPreferenceAction::class)->execute(new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));

        $this->assertSame('pref_test_123', $result->preferenceId);
        $this->assertSame('buyer@example.com', $gateway->data?->email);
        $this->assertStringStartsWith('cart-test-', (string) $gateway->data?->externalReference);
        $this->assertSame([
            [
                'id' => (string) $product->getKey(),
                'title' => 'Phantom Assassin Arcana',
                'quantity' => 2,
                'unit_price' => 1399.0,
                'currency_id' => 'BRL',
            ],
        ], $gateway->data?->items);
        $this->assertSame(route('storefront.mercado-pago.success'), $gateway->data?->backUrls['success']);
        $this->assertDatabaseCount((new Order)->getTable(), 0);
        $this->assertSame(5, $product->refresh()->quantity);
        $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
    }

    #[Test]
    public function it_rejects_invalid_contact_data_before_creating_a_preference(): void
    {
        Product::factory()->create();

        $this->expectException(InvalidCheckoutContact::class);

        app(CreateCheckoutPreferenceAction::class)->execute(new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '123',
        ));
    }
}
