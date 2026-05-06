<?php

namespace Tests\Feature\Payments;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\ClearCartAction;
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
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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
        URL::forceRootUrl('https://gains-bootlace-slacking.ngrok-free.dev');

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
                    rawProviderResponse: ['id' => 'pref_test_123'],
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
        $this->assertSame(
            'https://gains-bootlace-slacking.ngrok-free.dev/checkout/mercado-pago/success',
            $gateway->data?->backUrls['success'],
        );

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->where('external_reference', $gateway->data?->externalReference)->firstOrFail();

        $this->assertSame(OrderStatus::PendingPayment->value, $order->status);
        $this->assertSame($order->getKey(), $payment->order_id);
        $this->assertSame(PaymentProvider::MercadoPago->value, $payment->provider);
        $this->assertSame(PaymentStatus::Pending->value, $payment->status);
        $this->assertSame('pref_test_123', $payment->provider_preference_id);
        $this->assertSame('https://sandbox.mercadopago.test/checkout', $payment->checkout_url);
        $this->assertSame('TEST-public-key', $payment->metadata['checkout_preference_public_key']);
        $this->assertSame('init_point', $payment->metadata['checkout_url_strategy']);
        $this->assertNotEmpty($payment->metadata['checkout_intent_hash']);
        $this->assertSame(['id' => 'pref_test_123'], $payment->raw_provider_snapshot);
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
    public function it_reuses_a_pending_preference_for_the_same_cart_intent(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                    rawProviderResponse: ['id' => 'pref_test_123'],
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create([
            'price' => 5000,
            'quantity' => 3,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        $data = new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        );

        $firstResult = app(CreateCheckoutPreferenceAction::class)->execute($data);
        $secondResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        $this->assertSame('pref_test_123', $firstResult->preferenceId);
        $this->assertSame('pref_test_123', $secondResult->preferenceId);
        $this->assertSame('TEST-public-key', $secondResult->publicKey);
        $this->assertSame('https://sandbox.mercadopago.test/checkout', $secondResult->checkoutUrl);
        $this->assertSame(1, $gateway->calls);
        $this->assertDatabaseCount((new Order)->getTable(), 1);
        $this->assertDatabaseCount((new Payment)->getTable(), 1);
        $this->assertSame(2, $product->refresh()->quantity);
    }

    #[Test]
    public function it_reuses_the_session_pending_preference_when_the_cart_was_cleared_after_checkout_start(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                    rawProviderResponse: ['id' => 'pref_test_123'],
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create([
            'price' => 5000,
            'quantity' => 3,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        $data = new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        );

        $firstResult = app(CreateCheckoutPreferenceAction::class)->execute($data);
        app(ClearCartAction::class)->execute();
        $secondResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        $this->assertSame('pref_test_123', $firstResult->preferenceId);
        $this->assertSame('pref_test_123', $secondResult->preferenceId);
        $this->assertSame(1, $gateway->calls);
        $this->assertDatabaseCount((new Order)->getTable(), 1);
        $this->assertDatabaseCount((new Payment)->getTable(), 1);
        $this->assertSame(2, $product->refresh()->quantity);
    }

    #[Test]
    public function it_does_not_reuse_the_session_pending_preference_when_the_cart_changes(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_'.$this->calls,
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout/'.$this->calls,
                    rawProviderResponse: ['id' => 'pref_test_'.$this->calls],
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $firstProduct = Product::factory()->create([
            'price' => 5000,
            'quantity' => 3,
        ]);
        $secondProduct = Product::factory()->create([
            'price' => 7000,
            'quantity' => 3,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $firstProduct->getKey(),
            quantity: 1,
        ));

        $data = new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        );

        $firstResult = app(CreateCheckoutPreferenceAction::class)->execute($data);
        app(ClearCartAction::class)->execute();
        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $secondProduct->getKey(),
            quantity: 1,
        ));

        $secondResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        $this->assertSame('pref_test_1', $firstResult->preferenceId);
        $this->assertSame('pref_test_2', $secondResult->preferenceId);
        $this->assertSame(2, $gateway->calls);
        $this->assertDatabaseCount((new Order)->getTable(), 2);
        $this->assertDatabaseCount((new Payment)->getTable(), 2);
        $this->assertSame(2, $firstProduct->refresh()->quantity);
        $this->assertSame(2, $secondProduct->refresh()->quantity);
    }

    #[Test]
    public function it_does_not_reuse_an_expired_pending_preference_for_the_same_cart_intent(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_'.$this->calls,
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout/'.$this->calls,
                    rawProviderResponse: ['id' => 'pref_test_'.$this->calls],
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create([
            'price' => 5000,
            'quantity' => 5,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        $data = new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        );

        $firstResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        Payment::query()->firstOrFail()
            ->forceFill([
                'created_at' => now()->subMinutes(31),
                'updated_at' => now()->subMinutes(31),
            ])
            ->save();

        $secondResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        $this->assertSame('pref_test_1', $firstResult->preferenceId);
        $this->assertSame('pref_test_2', $secondResult->preferenceId);
        $this->assertSame(2, $gateway->calls);
        $this->assertDatabaseCount((new Order)->getTable(), 2);
        $this->assertDatabaseCount((new Payment)->getTable(), 2);
        $this->assertSame(3, $product->refresh()->quantity);
    }

    #[Test]
    public function it_stores_the_payment_amount_from_locked_order_item_prices(): void
    {
        $this->app->instance(CheckoutPreferenceGateway::class, new class implements CheckoutPreferenceGateway
        {
            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                );
            }
        });

        $product = Product::factory()->create([
            'price' => 1000,
            'quantity' => 2,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        $product->update(['price' => 2500]);

        app(CreateCheckoutPreferenceAction::class)->execute(new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));

        $payment = Payment::query()->firstOrFail();

        $this->assertSame(2500, $payment->amount_cents);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $payment->order_id,
            'product_id' => $product->getKey(),
            'unit_price' => 2500,
            'quantity' => 1,
        ]);
    }

    #[Test]
    public function it_reuses_a_pending_preference_without_requiring_the_public_key_for_redirect_checkout(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: '',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create([
            'price' => 5000,
            'quantity' => 3,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        $data = new CreateCheckoutPreferenceData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        );

        app(CreateCheckoutPreferenceAction::class)->execute($data);

        config(['services.mercado_pago.public_key' => '']);

        $secondResult = app(CreateCheckoutPreferenceAction::class)->execute($data);

        $this->assertSame('pref_test_123', $secondResult->preferenceId);
        $this->assertSame('', $secondResult->publicKey);
        $this->assertSame('https://sandbox.mercadopago.test/checkout', $secondResult->checkoutUrl);
        $this->assertSame(1, $gateway->calls);
        $this->assertDatabaseCount((new Order)->getTable(), 1);
        $this->assertDatabaseCount((new Payment)->getTable(), 1);
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

    #[Test]
    public function it_restores_stock_and_discards_local_checkout_state_when_preference_has_no_checkout_url(): void
    {
        $this->app->instance(CheckoutPreferenceGateway::class, new class implements CheckoutPreferenceGateway
        {
            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: null,
                );
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
        } catch (PaymentConfigurationMissing) {
            $this->assertDatabaseCount((new Order)->getTable(), 0);
            $this->assertDatabaseCount((new Payment)->getTable(), 0);
            $this->assertSame(2, $product->refresh()->quantity);
            $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
        }
    }
}
