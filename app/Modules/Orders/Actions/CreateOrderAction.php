<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Cart\Actions\ClearCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Exceptions\InvalidOrderContact;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __construct(
        private readonly GetCurrentCartAction $getCurrentCartAction,
        private readonly ClearCartAction $clearCartAction,
    ) {}

    public function execute(CreateOrderData $data): Order
    {
        $this->guardContact($data);

        $cartItems = $this->getCurrentCartAction->execute();

        if ($cartItems === []) {
            throw new EmptyCart('Cart is empty.');
        }

        $this->guardCartItems($cartItems);

        $order = DB::transaction(function () use ($cartItems, $data): Order {
            $productIds = array_map(
                static fn (array $item): int => $item['product_id'],
                $cartItems,
            );

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem['product_id']);

                if ($product === null) {
                    throw new InsufficientStock('A cart product is no longer available.');
                }

                if ($product->quantity < $cartItem['quantity']) {
                    throw new InsufficientStock("Insufficient stock for product [{$product->getKey()}].");
                }
            }

            $order = Order::query()->create([
                'email' => $data->email,
                'whatsapp' => $data->whatsapp,
                'status' => Order::STATUS_PENDING_FULFILLMENT,
            ]);

            foreach ($cartItems as $cartItem) {
                /** @var Product $product */
                $product = $products->get($cartItem['product_id']);

                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $cartItem['quantity'],
                ]);

                $product->decrement('quantity', $cartItem['quantity']);
            }

            return $order->load('items');
        });

        $this->clearCartAction->execute();
        event(new OrderCreated($order));

        return $order;
    }

    private function guardCartItems(array $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $productId = $cartItem['product_id'] ?? null;
            $quantity = $cartItem['quantity'] ?? null;

            if (!is_int($productId) || $productId <= 0) {
                throw new InvalidProductReference('Cart contains an invalid product reference.');
            }

            if (!is_int($quantity) || $quantity <= 0) {
                throw new InvalidCartQuantity('Cart quantity must be greater than zero.');
            }
        }
    }

    private function guardContact(CreateOrderData $data): void
    {
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidOrderContact('Order email must be valid.');
        }

        $normalizedWhatsapp = preg_replace('/\D+/', '', $data->whatsapp) ?? '';

        if ($normalizedWhatsapp === '' || strlen($normalizedWhatsapp) < 10 || strlen($normalizedWhatsapp) > 15) {
            throw new InvalidOrderContact('WhatsApp number must contain between 10 and 15 digits.');
        }
    }
}
