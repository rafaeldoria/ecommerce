<?php

namespace Tests\Feature\Cart;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Actions\RemoveFromCartAction;
use App\Modules\Cart\Actions\UpdateCartItemAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Cart\DTOs\UpdateCartItemData;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['session']->start();
    }

    #[Test]
    public function add_to_cart_action_adds_a_new_item(): void
    {
        $product = Product::factory()->create([
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
        ]);

        $items = app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 2,
        ));

        $this->assertCount(1, $items);
        $this->assertSame($product->getKey(), $items[0]['product_id']);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertSame(159900, $items[0]['unit_price']);
        $this->assertSame('Dragonclaw Hook', $items[0]['product_name']);
    }

    #[Test]
    public function add_to_cart_action_consolidates_quantity_for_the_same_product(): void
    {
        $product = Product::factory()->create([
            'price' => 1500,
        ]);

        $action = app(AddToCartAction::class);

        $action->execute(new AddToCartData($product->getKey(), 1));
        $items = $action->execute(new AddToCartData($product->getKey(), 3));

        $this->assertCount(1, $items);
        $this->assertSame(4, $items[0]['quantity']);
    }

    #[Test]
    public function update_cart_item_action_updates_a_valid_quantity(): void
    {
        $product = Product::factory()->create();
        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 1));

        $items = app(UpdateCartItemAction::class)->execute(new UpdateCartItemData(
            productId: $product->getKey(),
            quantity: 5,
        ));

        $this->assertCount(1, $items);
        $this->assertSame(5, $items[0]['quantity']);
    }

    #[Test]
    public function cart_actions_reject_invalid_quantities(): void
    {
        $product = Product::factory()->create();

        $this->expectException(InvalidCartQuantity::class);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 0,
        ));
    }

    #[Test]
    public function cart_actions_reject_missing_products(): void
    {
        $this->expectException(InvalidProductReference::class);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: 999,
            quantity: 1,
        ));
    }

    #[Test]
    public function remove_from_cart_action_is_idempotent(): void
    {
        $product = Product::factory()->create();
        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 1));

        $action = app(RemoveFromCartAction::class);
        $action->execute($product->getKey());
        $items = $action->execute($product->getKey());

        $this->assertSame([], $items);
    }

    #[Test]
    public function get_current_cart_action_returns_the_expected_structure(): void
    {
        $product = Product::factory()->create([
            'name' => 'Immortal Sword',
            'price' => 3000,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 2));

        $items = app(GetCurrentCartAction::class)->execute();

        $this->assertSame([
            [
                'product_id' => $product->getKey(),
                'quantity' => 2,
                'unit_price' => 3000,
                'product_name' => 'Immortal Sword',
            ],
        ], $items);
    }
}
