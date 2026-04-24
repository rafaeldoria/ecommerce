<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_an_empty_cart_payload(): void
    {
        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.cart.retrieved'))
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.total_amount', 0);
    }

    #[Test]
    public function it_adds_updates_and_removes_cart_items_via_api(): void
    {
        $product = Product::factory()->create([
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
        ]);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->getKey(),
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.cart.item_added'))
            ->assertJsonPath('data.items.0.product_id', $product->getKey())
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.total_amount', 319800);

        $this->patchJson("/api/cart/items/{$product->getKey()}", [
            'quantity' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.cart.item_updated'))
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.total_quantity', 5)
            ->assertJsonPath('data.total_amount', 799500);

        $this->deleteJson("/api/cart/items/{$product->getKey()}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.cart.item_removed'))
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.total_amount', 0);
    }

    #[Test]
    public function it_returns_domain_errors_for_invalid_cart_mutation(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'product_id' => 999,
            'quantity' => 1,
        ]);

        $this->assertProblemDetails(
            $response,
            'invalid_product_reference',
            404,
            __('general.errors.invalid_product_reference'),
        );
    }
}
