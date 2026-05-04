<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\DTOs\CreatePendingCheckoutPaymentData;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePendingCheckoutPaymentAction
{
    public function __construct(
        private readonly GetCurrentCartAction $getCurrentCartAction,
    ) {}

    public function execute(CreatePendingCheckoutPaymentData $data): Payment
    {
        $this->guardContact($data);

        $cartItems = $this->getCurrentCartAction->execute();

        if ($cartItems === []) {
            throw new EmptyCart(__('general.errors.empty_cart'));
        }

        $this->guardCartItems($cartItems);

        return DB::transaction(function () use ($cartItems, $data): Payment {
            $products = $this->lockCartProducts($cartItems);

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem['product_id']);

                if ($product === null) {
                    throw new InsufficientStock(__('general.errors.cart_product_unavailable'));
                }

                if ($product->quantity < $cartItem['quantity']) {
                    throw new InsufficientStock(__('general.errors.insufficient_stock', [
                        'product_id' => $product->getKey(),
                    ]));
                }
            }

            $order = Order::query()->create([
                'email' => $data->email,
                'whatsapp' => $data->whatsapp,
                'status' => OrderStatus::PendingPayment->value,
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

            return Payment::query()
                ->create([
                    'order_id' => $order->getKey(),
                    'provider' => PaymentProvider::MercadoPago->value,
                    'external_reference' => Str::uuid()->toString(),
                    'amount_cents' => $this->amountCents($cartItems),
                    'currency' => 'BRL',
                    'status' => PaymentStatus::Pending->value,
                    'metadata' => [
                        'cart_item_count' => count($cartItems),
                    ],
                ])
                ->load('order.items');
        });
    }

    private function guardCartItems(array $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $productId = $cartItem['product_id'] ?? null;
            $quantity = $cartItem['quantity'] ?? null;
            $unitPrice = $cartItem['unit_price'] ?? null;

            if (!is_int($productId) || $productId <= 0) {
                throw new InvalidProductReference(__('general.errors.invalid_product_reference'));
            }

            if (!is_int($quantity) || $quantity <= 0) {
                throw new InvalidCartQuantity(__('general.errors.invalid_cart_quantity'));
            }

            if (!is_int($unitPrice) || $unitPrice < 0) {
                throw new InvalidCartQuantity(__('general.errors.invalid_cart_quantity'));
            }
        }
    }

    private function guardContact(CreatePendingCheckoutPaymentData $data): void
    {
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidCheckoutContact(__('general.errors.invalid_order_email'));
        }

        $normalizedWhatsapp = preg_replace('/\D+/', '', $data->whatsapp) ?? '';

        if ($normalizedWhatsapp === '' || strlen($normalizedWhatsapp) < 10 || strlen($normalizedWhatsapp) > 15) {
            throw new InvalidCheckoutContact(__('general.errors.invalid_order_whatsapp'));
        }
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function lockCartProducts(array $cartItems): Collection
    {
        $productIds = array_map(
            static fn (array $item): int => $item['product_id'],
            $cartItems,
        );

        return Product::query()
            ->whereIn('id', $productIds)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, array{quantity: int, unit_price: int}>  $cartItems
     */
    private function amountCents(array $cartItems): int
    {
        return array_reduce(
            $cartItems,
            static fn (int $total, array $item): int => $total + ($item['quantity'] * $item['unit_price']),
            0,
        );
    }
}
