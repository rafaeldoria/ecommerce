<?php

namespace Tests\Feature\Payments;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\ProcessMercadoPagoPaymentUpdateAction;
use App\Modules\Payments\DTOs\MercadoPagoPaymentDetails;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentStatusProcessorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function approved_accredited_payment_marks_the_order_completed(): void
    {
        $payment = $this->payment();

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute(new MercadoPagoPaymentDetails(
            paymentId: '123456',
            externalReference: $payment->external_reference,
            status: 'approved',
            statusDetail: 'accredited',
            amountCents: 2590,
            metadata: ['payment_type_id' => 'credit_card'],
        ));

        $this->assertSame(OrderStatus::Completed, $payment->order->refresh()->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'mercado_pago_payment_id' => '123456',
            'status' => 'approved',
            'status_detail' => 'accredited',
            'amount_cents' => 2590,
        ]);
    }

    #[Test]
    public function repeated_webhook_updates_are_idempotent(): void
    {
        $payment = $this->payment();
        $details = new MercadoPagoPaymentDetails(
            paymentId: '123456',
            externalReference: $payment->external_reference,
            status: 'rejected',
            statusDetail: 'cc_rejected_other_reason',
            amountCents: 2590,
        );

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute($details);
        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute($details);

        $this->assertSame(OrderStatus::Error, $payment->order->refresh()->status);
        $this->assertDatabaseCount('payments', 1);
    }

    #[Test]
    public function pending_updates_do_not_downgrade_completed_orders(): void
    {
        $payment = $this->payment(OrderStatus::Completed);

        app(ProcessMercadoPagoPaymentUpdateAction::class)->execute(new MercadoPagoPaymentDetails(
            paymentId: '123456',
            externalReference: $payment->external_reference,
            status: 'pending',
            statusDetail: 'pending_waiting_payment',
            amountCents: 2590,
        ));

        $this->assertSame(OrderStatus::Completed, $payment->order->refresh()->status);
        $this->assertSame('pending', $payment->refresh()->status);
    }

    private function payment(OrderStatus $status = OrderStatus::Pending): Payment
    {
        $product = Product::factory()->create(['price' => 2590]);
        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => $status,
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
