<?php

namespace Tests\Feature\Payments;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\MercadoPagoPaymentDetails;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_rejects_webhooks_with_invalid_signatures(): void
    {
        config(['services.mercado_pago.webhook_secret' => 'secret']);

        $this->postJson(route('webhooks.mercado-pago', ['data.id' => '123456']), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function it_fetches_and_processes_payment_details_for_valid_webhooks(): void
    {
        config([
            'services.mercado_pago.webhook_secret' => 'secret',
            'services.mercado_pago.webhook_tolerance_seconds' => 300,
        ]);

        $payment = $this->payment();

        $this->app->instance(PaymentDetailsGateway::class, new class($payment->external_reference) implements PaymentDetailsGateway
        {
            public function __construct(private readonly string $externalReference) {}

            public function get(string $paymentId): MercadoPagoPaymentDetails
            {
                return new MercadoPagoPaymentDetails(
                    paymentId: $paymentId,
                    externalReference: $this->externalReference,
                    status: 'approved',
                    statusDetail: 'accredited',
                    amountCents: 2590,
                );
            }
        });

        $paymentId = '123456';
        $requestId = 'request-123';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "id:{$paymentId};request-id:{$requestId};ts:{$timestamp};", 'secret');

        $this->postJson(route('webhooks.mercado-pago', ['data.id' => $paymentId]), [], [
            'x-request-id' => $requestId,
            'x-signature' => "ts={$timestamp},v1={$signature}",
        ])->assertOk();

        $this->assertSame(OrderStatus::Completed, $payment->order->refresh()->status);
        $this->assertSame('approved', $payment->refresh()->status);
    }

    #[Test]
    public function it_returns_a_retryable_error_when_verified_processing_fails(): void
    {
        config([
            'services.mercado_pago.webhook_secret' => 'secret',
            'services.mercado_pago.webhook_tolerance_seconds' => 300,
        ]);

        $this->app->instance(PaymentDetailsGateway::class, new class implements PaymentDetailsGateway
        {
            public function get(string $paymentId): MercadoPagoPaymentDetails
            {
                throw new \RuntimeException('Mercado Pago unavailable.');
            }
        });

        $paymentId = '123456';
        $requestId = 'request-123';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "id:{$paymentId};request-id:{$requestId};ts:{$timestamp};", 'secret');

        $this->postJson(route('webhooks.mercado-pago', ['data.id' => $paymentId]), [], [
            'x-request-id' => $requestId,
            'x-signature' => "ts={$timestamp},v1={$signature}",
        ])->assertStatus(500);
    }

    private function payment(): Payment
    {
        $product = Product::factory()->create(['price' => 2590]);
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

        return Payment::query()->create([
            'order_id' => $order->getKey(),
            'external_reference' => 'payment-'.$order->getKey().'-test',
            'amount_cents' => 2590,
        ]);
    }
}
