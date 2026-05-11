<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\MercadoPagoWebhookRequest;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_list_and_view_orders_with_buyer_contact_data(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create([
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
            'quantity' => 5,
        ]);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->getKey(),
            'quantity' => 2,
        ])->assertCreated();

        $orderId = $this->postJson('/api/orders', [
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
        ])->assertCreated()->json('data.id');

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.orders.listed'))
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonFragment(['email' => 'buyer@example.com'])
            ->assertJsonFragment(['whatsapp' => '+55 11 98888-7777'])
            ->assertJsonFragment(['product_name' => 'Dragonclaw Hook']);

        $this->getJson("/api/admin/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.orders.retrieved'))
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.status', OrderStatus::PendingFulfillment->value)
            ->assertJsonPath('data.total_amount', 319800)
            ->assertJsonPath('data.items.0.quantity', 2);
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_orders(): void
    {
        $response = $this->getJson('/api/admin/orders');

        $this->assertProblemDetails(
            $response,
            'unauthenticated',
            401,
            __('general.api.errors.unauthenticated'),
        );
    }

    #[Test]
    public function admin_order_detail_includes_sanitized_latest_payment_context(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create([
            'name' => 'AK-47 Redline',
            'price' => 2590,
            'quantity' => 3,
        ]);
        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
            'status' => OrderStatus::Paid->value,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => 2590,
            'quantity' => 1,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => PaymentProvider::MercadoPago->value,
            'provider_payment_id' => 'mp-payment-456',
            'external_reference' => 'payment-external-reference-456',
            'amount_cents' => 2590,
            'currency' => 'BRL',
            'status' => PaymentStatus::Approved->value,
            'provider_status' => 'approved',
            'provider_status_detail' => 'accredited',
            'raw_provider_snapshot' => [
                'card' => ['last_four_digits' => '1234'],
                'secret' => 'must-not-leak',
            ],
        ]);

        MercadoPagoWebhookRequest::query()->create([
            'received_at' => now(),
            'processing_status' => 'processed',
            'http_status_returned' => 200,
            'event_type' => 'payment',
            'data_id' => 'mp-payment-456',
            'signature_valid' => true,
            'related_payment_id' => $payment->getKey(),
            'provider_payment_id' => 'mp-payment-456',
            'processed_at' => now(),
        ]);

        $response = $this->getJson("/api/admin/orders/{$order->getKey()}");

        $response
            ->assertOk()
            ->assertJsonPath('data.latest_payment.status', PaymentStatus::Approved->value)
            ->assertJsonPath('data.latest_payment.provider_payment_id', 'mp-payment-456')
            ->assertJsonPath('data.latest_payment.provider_status_detail', 'accredited')
            ->assertJsonPath('data.latest_payment.webhook_journal.processing_status', 'processed')
            ->assertJsonMissing(['secret' => 'must-not-leak'])
            ->assertJsonMissing(['last_four_digits' => '1234']);
    }

    #[Test]
    public function customers_cannot_access_admin_orders(): void
    {
        $this->actingAsCustomer();

        $response = $this->getJson('/api/admin/orders');

        $this->assertProblemDetails(
            $response,
            'forbidden',
            403,
            __('general.api.errors.forbidden'),
        );
    }
}
