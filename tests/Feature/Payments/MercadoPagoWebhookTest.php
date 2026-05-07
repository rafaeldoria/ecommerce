<?php

namespace Tests\Feature\Payments;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\ProviderPaymentDetails;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\MercadoPagoWebhookRequest;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercado_pago.webhook_secret' => $this->secret,
            'services.mercado_pago.webhook_signature_tolerance_seconds' => 0,
        ]);
    }

    #[Test]
    public function it_accepts_a_valid_payment_webhook_and_stores_the_journal(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: '123456',
            externalReference: $payment->external_reference,
            status: 'approved',
            statusDetail: 'accredited',
            amountCents: 10000,
            currency: 'BRL',
            rawProviderResponse: ['id' => '123456', 'status' => 'approved'],
        ));

        $payload = $this->paymentPayload(dataId: '123456');
        $headers = $this->signedHeaders(dataId: '123456');

        $response = $this->postJson('/webhooks/mercado-pago?data.id=123456&type=payment', $payload, $headers);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'processed');

        $journal = MercadoPagoWebhookRequest::query()->firstOrFail();

        $this->assertSame('processed', $journal->processing_status);
        $this->assertSame(200, $journal->http_status_returned);
        $this->assertSame('payment', $journal->event_type);
        $this->assertSame('payment.updated', $journal->event_action);
        $this->assertSame('123456', $journal->notification_id);
        $this->assertSame('123456', $journal->data_id);
        $this->assertFalse($journal->live_mode);
        $this->assertSame('724484980', $journal->user_id);
        $this->assertSame('bb56a2f1-6aae-46ac-982e-9dcd3581d08e', $journal->x_request_id);
        $this->assertTrue($journal->signature_valid);
        $this->assertSame('123456', $journal->provider_payment_id);
        $this->assertSame('123456', $journal->query['data.id']);
        $this->assertSame('123456', $journal->payload['data']['id']);
        $this->assertArrayHasKey('x-signature', $journal->headers);
        $this->assertArrayNotHasKey('authorization', $journal->headers);
        $this->assertSame($payment->getKey(), $journal->related_payment_id);
        $this->assertNotNull($journal->processed_at);
        $this->assertSame(OrderStatus::Paid->value, $payment->order->refresh()->status);
        $this->assertSame(PaymentStatus::Approved->value, $payment->refresh()->status);
    }

    #[Test]
    public function it_rejects_an_invalid_signature_and_keeps_the_journal(): void
    {
        $payload = $this->paymentPayload(dataId: '123456');
        $headers = $this->signedHeaders(dataId: '123456', signature: 'ts=1742505638683,v1=invalid');

        $response = $this->postJson('/webhooks/mercado-pago?data.id=123456&type=payment', $payload, $headers);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('status', 'invalid_signature');

        $journal = MercadoPagoWebhookRequest::query()->firstOrFail();

        $this->assertSame('failed', $journal->processing_status);
        $this->assertSame(401, $journal->http_status_returned);
        $this->assertFalse($journal->signature_valid);
        $this->assertSame('signature_mismatch', $journal->validation_error);
        $this->assertSame('123456', $journal->data_id);
    }

    #[Test]
    public function it_rejects_a_missing_signature_header_and_keeps_the_journal(): void
    {
        $payload = $this->paymentPayload(dataId: '123456');

        $response = $this->postJson('/webhooks/mercado-pago?data.id=123456&type=payment', $payload, [
            'X-Request-Id' => 'bb56a2f1-6aae-46ac-982e-9dcd3581d08e',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('status', 'invalid_signature');

        $journal = MercadoPagoWebhookRequest::query()->firstOrFail();

        $this->assertSame('failed', $journal->processing_status);
        $this->assertFalse($journal->signature_valid);
        $this->assertSame('signature_header_missing', $journal->validation_error);
    }

    #[Test]
    public function it_ignores_valid_unsupported_topics(): void
    {
        $payload = $this->paymentPayload(dataId: '123456', type: 'merchant_order', action: 'merchant_order.updated');
        $headers = $this->signedHeaders(dataId: '123456');

        $response = $this->postJson('/webhooks/mercado-pago?data.id=123456&type=merchant_order', $payload, $headers);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ignored');

        $journal = MercadoPagoWebhookRequest::query()->firstOrFail();

        $this->assertSame('ignored', $journal->processing_status);
        $this->assertSame(200, $journal->http_status_returned);
        $this->assertTrue($journal->signature_valid);
        $this->assertSame('merchant_order', $journal->event_type);
        $this->assertSame('unsupported_event_type', $journal->error_message);
        $this->assertNull($journal->provider_payment_id);
    }

    #[Test]
    public function it_ignores_legacy_ipn_feed_notifications_without_signature_validation(): void
    {
        $response = $this->postJson('/webhooks/mercado-pago?id=157069132993&topic=payment', [], [
            'X-Request-Id' => 'bb56a2f1-6aae-46ac-982e-9dcd3581d08e',
            'X-Signature' => 'ts=1742505638,v1=invalid-feed-signature',
            'User-Agent' => 'MercadoPago Feed v2.0 payment',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ignored_ipn');

        $journal = MercadoPagoWebhookRequest::query()->firstOrFail();

        $this->assertSame('ignored_ipn', $journal->processing_status);
        $this->assertSame(200, $journal->http_status_returned);
        $this->assertSame('payment', $journal->event_type);
        $this->assertSame('157069132993', $journal->data_id);
        $this->assertNull($journal->signature_valid);
        $this->assertNull($journal->validation_error);
        $this->assertSame('legacy_ipn_feed_notification', $journal->error_message);
        $this->assertNull($journal->provider_payment_id);
    }

    #[Test]
    public function it_accepts_the_webhook_route_without_a_csrf_token(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: '123456',
            externalReference: $payment->external_reference,
            status: 'pending',
            statusDetail: 'pending_waiting_payment',
            amountCents: 10000,
            currency: 'BRL',
            rawProviderResponse: ['id' => '123456', 'status' => 'pending'],
        ));

        $headers = $this->signedHeaders(dataId: '123456');

        $response = $this->post('/webhooks/mercado-pago?data.id=123456&type=payment', [], $headers);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'processed');

        $this->assertSame('processed', MercadoPagoWebhookRequest::query()->firstOrFail()->processing_status);
    }

    private function fakeGateway(ProviderPaymentDetails $details): void
    {
        $this->app->instance(PaymentDetailsGateway::class, new class($details) implements PaymentDetailsGateway
        {
            public function __construct(private readonly ProviderPaymentDetails $details) {}

            public function find(string $providerPaymentId): ProviderPaymentDetails
            {
                return $this->details;
            }
        });
    }

    private function pendingPayment(): Payment
    {
        $product = Product::factory()->create(['quantity' => 4]);
        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => 10000,
            'quantity' => 1,
        ]);

        return Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => PaymentProvider::MercadoPago->value,
            'external_reference' => 'payment-reference-'.$order->getKey(),
            'amount_cents' => 10000,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending->value,
        ])->load('order.items');
    }

    private function signedHeaders(string $dataId, ?string $signature = null): array
    {
        $requestId = 'bb56a2f1-6aae-46ac-982e-9dcd3581d08e';
        $timestamp = '1742505638683';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $signature ??= 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, $this->secret);

        return [
            'X-Request-Id' => $requestId,
            'X-Signature' => $signature,
            'Authorization' => 'Bearer should-not-be-stored',
        ];
    }

    private function paymentPayload(
        string $dataId,
        string $type = 'payment',
        string $action = 'payment.updated',
    ): array {
        return [
            'action' => $action,
            'api_version' => 'v1',
            'data' => [
                'id' => $dataId,
            ],
            'date_created' => '2021-11-01T02:02:02Z',
            'id' => $dataId,
            'live_mode' => false,
            'type' => $type,
            'user_id' => 724484980,
        ];
    }
}
