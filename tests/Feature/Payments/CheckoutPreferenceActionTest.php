<?php

namespace Tests\Feature\Payments;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\CreateCheckoutPreferenceAction;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\CreateCheckoutPreferenceData;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CheckoutPreferenceActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_pending_order_and_payment_before_returning_the_checkout_preference(): void
    {
        Event::fake();

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
        $this->assertTrue(Str::isUuid((string) $gateway->data?->externalReference));
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

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->where('external_reference', $gateway->data?->externalReference)->firstOrFail();

        $this->assertSame(OrderStatus::PendingPayment->value, $order->status);
        $this->assertSame($order->getKey(), $payment->order_id);
        $this->assertSame(PaymentProvider::MercadoPago->value, $payment->provider);
        $this->assertSame(PaymentStatus::Pending->value, $payment->status);
        $this->assertSame('pref_test_123', $payment->provider_preference_id);
        $this->assertSame('https://sandbox.mercadopago.test/checkout', $payment->checkout_url);
        $this->assertSame(279800, $payment->amount_cents);
        $this->assertSame('BRL', $payment->currency);
        $this->assertNull($payment->provider_payment_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'unit_price' => 139900,
            'quantity' => 2,
        ]);
        $this->assertSame(3, $product->refresh()->quantity);
        $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
        Event::assertNotDispatched(OrderCreated::class);
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

    #[Test]
    public function it_restores_stock_and_discards_local_checkout_state_when_preference_creation_fails(): void
    {
        $this->app->instance(CheckoutPreferenceGateway::class, new class implements CheckoutPreferenceGateway
        {
            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                throw new RuntimeException('Mercado Pago unavailable.');
            }
        });

        $product = Product::factory()->create([
            'price' => 10000,
            'quantity' => 2,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        try {
            app(CreateCheckoutPreferenceAction::class)->execute(new CreateCheckoutPreferenceData(
                email: 'buyer@example.com',
                whatsapp: '+55 11 99999-1111',
            ));

            $this->fail('Preference creation should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mercado Pago unavailable.', $exception->getMessage());
        }

        $this->assertDatabaseCount((new Order)->getTable(), 0);
        $this->assertDatabaseCount((new Payment)->getTable(), 0);
        $this->assertSame(2, $product->refresh()->quantity);
        $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
    }
}
