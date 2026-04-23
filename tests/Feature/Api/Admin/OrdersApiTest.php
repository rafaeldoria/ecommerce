<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
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
            ->assertJsonFragment(['email' => 'buyer@example.com'])
            ->assertJsonFragment(['whatsapp' => '+55 11 98888-7777'])
            ->assertJsonFragment(['product_name' => 'Dragonclaw Hook']);

        $this->getJson("/api/admin/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.orders.retrieved'))
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.status', Order::STATUS_PENDING_FULFILLMENT)
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
