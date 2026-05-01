<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_order_from_the_current_api_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Immortal Sword',
            'price' => 3000,
            'quantity' => 5,
        ]);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->getKey(),
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/orders', [
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
        ])
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.orders.created'))
            ->assertJsonPath('data.email', 'buyer@example.com')
            ->assertJsonPath('data.status', OrderStatus::Pending->value)
            ->assertJsonPath('data.items.0.product_id', $product->getKey());

        $this->assertDatabaseHas('orders', [
            'email' => 'buyer@example.com',
            'status' => OrderStatus::Pending->value,
        ]);
    }

    #[Test]
    public function it_returns_a_domain_error_when_creating_an_order_from_an_empty_cart(): void
    {
        $response = $this->postJson('/api/orders', [
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
        ]);

        $this->assertProblemDetails(
            $response,
            'empty_cart',
            422,
            __('general.errors.empty_cart'),
        );
    }
}
