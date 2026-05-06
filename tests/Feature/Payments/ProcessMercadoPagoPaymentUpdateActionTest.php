<?php

namespace Tests\Feature\Payments;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\ProcessMercadoPagoPaymentUpdateAction;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\ProviderPaymentDetails;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessMercadoPagoPaymentUpdateActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_the_local_order_paid_from_an_accredited_mercado_pago_payment(): void
    {
        $payment = $this->pendingPayment();

        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: 'mp-payment-123',
            externalReference: $payment->external_reference,
            status: 'approved',
            statusDetail: 'accredited',
            amountCents: 10000,
            currency: 'BRL',
            rawProviderResponse: ['id' => 'mp-payment-123', 'status' => 'approved'],
        ));

        $result = app(ProcessMercadoPagoPaymentUpdateAction::class)->execute('mp-payment-123');

        $this->assertSame('processed', $result->status);
        $this->assertSame($payment->getKey(), $result->paymentId);
        $this->assertSame(OrderStatus::Paid->value, $payment->order->refresh()->status);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Approved->value, $payment->status);
        $this->assertSame('mp-payment-123', $payment->provider_payment_id);
        $this->assertSame('approved', $payment->provider_status);
        $this->assertSame('accredited', $payment->provider_status_detail);
        $this->assertSame(10000, $payment->metadata['provider_amount_cents']);
        $this->assertSame(['id' => 'mp-payment-123', 'status' => 'approved'], $payment->raw_provider_snapshot);
    }

    #[Test]
    public function it_restores_reserved_stock_once_for_failed_terminal_payments(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);
        $payment = $this->pendingPayment($product, quantity: 2);

        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: 'mp-payment-456',
            externalReference: $payment->external_reference,
            status: 'rejected',
            statusDetail: 'cc_rejected_insufficient_amount',
            amountCents: 20000,
            currency: 'BRL',
            rawProviderResponse: ['id' => 'mp-payment-456', 'status' => 'rejected'],
        ));

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute('mp-payment-456');
        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute('mp-payment-456');

        $payment->refresh();

        $this->assertSame(PaymentStatus::Rejected->value, $payment->status);
        $this->assertSame(OrderStatus::PaymentFailed->value, $payment->order->refresh()->status);
        $this->assertSame(5, $product->refresh()->quantity);
        $this->assertArrayHasKey('stock_restored_at', $payment->metadata);
        $this->assertSame('mp-payment-456', $payment->metadata['stock_restored_provider_payment_id']);
    }

    #[Test]
    public function it_preserves_unknown_provider_status_without_marking_the_order_paid(): void
    {
        $payment = $this->pendingPayment();

        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: 'mp-payment-789',
            externalReference: $payment->external_reference,
            status: 'mystery_state',
            statusDetail: 'unexpected_detail',
            amountCents: null,
            currency: null,
            rawProviderResponse: ['id' => 'mp-payment-789', 'status' => 'mystery_state'],
        ));

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute('mp-payment-789');

        $payment->refresh();

        $this->assertSame(PaymentStatus::Unknown->value, $payment->status);
        $this->assertSame('mystery_state', $payment->provider_status);
        $this->assertSame('unexpected_detail', $payment->provider_status_detail);
        $this->assertSame(OrderStatus::PendingPayment->value, $payment->order->refresh()->status);
    }

    #[Test]
    public function it_does_not_mark_the_order_paid_when_the_approved_payment_amount_does_not_match(): void
    {
        $payment = $this->pendingPayment();

        $this->fakeGateway(new ProviderPaymentDetails(
            providerPaymentId: 'mp-payment-amount-mismatch',
            externalReference: $payment->external_reference,
            status: 'approved',
            statusDetail: 'accredited',
            amountCents: 5000,
            currency: 'BRL',
            rawProviderResponse: ['id' => 'mp-payment-amount-mismatch', 'status' => 'approved'],
        ));

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute('mp-payment-amount-mismatch');

        $payment->refresh();

        $this->assertSame(PaymentStatus::Unknown->value, $payment->status);
        $this->assertSame('approved', $payment->provider_status);
        $this->assertTrue($payment->metadata['provider_payment_mismatch']);
        $this->assertSame(OrderStatus::PendingPayment->value, $payment->order->refresh()->status);
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

    private function pendingPayment(?Product $product = null, int $quantity = 1): Payment
    {
        $product ??= Product::factory()->create(['quantity' => 4]);

        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => 10000,
            'quantity' => $quantity,
        ]);

        return Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => PaymentProvider::MercadoPago->value,
            'external_reference' => 'payment-reference-'.$order->getKey(),
            'amount_cents' => 10000 * $quantity,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending->value,
        ])->load('order.items');
    }
}
