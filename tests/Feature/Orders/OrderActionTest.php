<?php

namespace Tests\Feature\Orders;

use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Exceptions\InvalidOrderContact;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class OrderActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['session']->start();
    }

    #[Test]
    public function create_order_action_creates_an_order_from_the_current_cart(): void
    {
        Event::fake();

        $product = Product::factory()->create([
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
            'quantity' => 5,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 2));

        $order = app(CreateOrderAction::class)->execute(new CreateOrderData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-1111',
            'status' => OrderStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => 'Dragonclaw Hook',
            'unit_price' => 159900,
            'quantity' => 2,
        ]);
        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertSame([], app(GetCurrentCartAction::class)->execute());
        Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->order->is($order));
    }

    #[Test]
    public function create_order_action_rejects_an_empty_cart(): void
    {
        $this->expectException(EmptyCart::class);

        app(CreateOrderAction::class)->execute(new CreateOrderData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));
    }

    #[Test]
    public function create_order_action_rejects_invalid_contact_input(): void
    {
        $product = Product::factory()->create();
        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 1));

        $this->expectException(InvalidOrderContact::class);

        app(CreateOrderAction::class)->execute(new CreateOrderData(
            email: 'invalid-email',
            whatsapp: '123',
        ));
    }

    #[Test]
    public function create_order_action_rejects_corrupted_cart_quantities(): void
    {
        $product = Product::factory()->create([
            'price' => 159900,
            'quantity' => 5,
        ]);

        $this->app['session']->put('cart.items', [
            (string) $product->getKey() => [
                'product_id' => $product->getKey(),
                'quantity' => 0,
                'unit_price' => $product->price,
                'product_name' => $product->name,
            ],
        ]);

        $this->expectException(InvalidCartQuantity::class);

        try {
            app(CreateOrderAction::class)->execute(new CreateOrderData(
                email: 'buyer@example.com',
                whatsapp: '+55 11 99999-1111',
            ));
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
            $this->assertSame(5, $product->fresh()->quantity);
            $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
        }
    }

    #[Test]
    public function create_order_action_rejects_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'quantity' => 1,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 2));

        $this->expectException(InsufficientStock::class);

        app(CreateOrderAction::class)->execute(new CreateOrderData(
            email: 'buyer@example.com',
            whatsapp: '+55 11 99999-1111',
        ));
    }

    #[Test]
    public function create_order_action_rolls_back_if_persisting_items_fails(): void
    {
        $product = Product::factory()->create([
            'quantity' => 5,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData($product->getKey(), 2));

        OrderItem::creating(static function (): void {
            throw new RuntimeException('Simulated order item failure.');
        });

        try {
            app(CreateOrderAction::class)->execute(new CreateOrderData(
                email: 'buyer@example.com',
                whatsapp: '+55 11 99999-1111',
            ));

            $this->fail('Order creation should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated order item failure.', $exception->getMessage());
        } finally {
            OrderItem::flushEventListeners();
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $product->fresh()->quantity);
        $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
    }
}
