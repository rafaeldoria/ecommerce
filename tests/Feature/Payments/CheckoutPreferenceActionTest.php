<?php

namespace Tests\Feature\Payments;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\StartPaymentCheckoutAction;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\StartPaymentCheckoutData;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutPreferenceActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['session']->start();
    }

    #[Test]
    public function it_creates_a_pending_order_and_payment_before_creating_the_preference(): void
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

        $result = app(StartPaymentCheckoutAction::class)->execute(new StartPaymentCheckoutData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));

        $this->assertSame('pref_test_123', $result->preference->preferenceId);
        $this->assertSame(OrderStatus::Pending, $result->order->status);
        $this->assertSame('buyer@example.com', $gateway->data?->email);
        $this->assertStringStartsWith('payment-'.$result->order->getKey().'-', (string) $gateway->data?->externalReference);
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
        $this->assertDatabaseHas('payments', [
            'id' => $result->payment->getKey(),
            'order_id' => $result->order->getKey(),
            'mercado_pago_preference_id' => 'pref_test_123',
            'amount_cents' => 279800,
        ]);
        $this->assertSame(3, $product->refresh()->quantity);
        $this->assertSame([], app(GetCurrentCartAction::class)->execute());
    }

    #[Test]
    public function it_reuses_an_existing_pending_payment_attempt(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_should_not_be_used',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/new-checkout',
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $product = Product::factory()->create();
        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => OrderStatus::Pending,
        ]);
        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->getKey(),
            'external_reference' => 'payment-'.$order->getKey().'-existing',
            'mercado_pago_preference_id' => 'pref_existing_123',
            'mercado_pago_checkout_url' => 'https://sandbox.mercadopago.test/existing-checkout',
            'amount_cents' => $product->price,
        ]);

        $result = app(StartPaymentCheckoutAction::class)->execute(new StartPaymentCheckoutData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
            existingPaymentId: $payment->getKey(),
        ));

        $this->assertSame('pref_existing_123', $result->preference->preferenceId);
        $this->assertSame('https://sandbox.mercadopago.test/existing-checkout', $result->preference->checkoutUrl);
        $this->assertSame(0, $gateway->calls);
    }

    #[Test]
    public function it_does_not_reuse_a_completed_payment_attempt_from_the_session(): void
    {
        $gateway = new class implements CheckoutPreferenceGateway
        {
            public int $calls = 0;

            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                $this->calls++;

                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_new_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/new-checkout',
                );
            }
        };

        $this->app->instance(CheckoutPreferenceGateway::class, $gateway);

        $oldOrder = Order::query()->create([
            'email' => 'old@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => OrderStatus::Completed,
        ]);
        $oldPayment = Payment::query()->create([
            'order_id' => $oldOrder->getKey(),
            'external_reference' => 'payment-'.$oldOrder->getKey().'-old',
            'mercado_pago_preference_id' => 'pref_old_123',
            'mercado_pago_checkout_url' => 'https://sandbox.mercadopago.test/old-checkout',
            'amount_cents' => 2590,
        ]);
        $product = Product::factory()->create([
            'price' => 2590,
            'quantity' => 2,
        ]);
        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 1));

        $result = app(StartPaymentCheckoutAction::class)->execute(new StartPaymentCheckoutData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
            existingPaymentId: $oldPayment->getKey(),
        ));

        $this->assertSame('pref_new_123', $result->preference->preferenceId);
        $this->assertNotSame($oldPayment->getKey(), $result->payment->getKey());
        $this->assertSame(1, $gateway->calls);
    }

    #[Test]
    public function it_rejects_invalid_contact_data_before_creating_a_preference(): void
    {
        Product::factory()->create();

        $this->expectException(InvalidCheckoutContact::class);

        app(StartPaymentCheckoutAction::class)->execute(new StartPaymentCheckoutData(
            email: 'buyer@example.com',
            whatsapp: '123',
        ));
    }
}
