<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Cart\Actions\ClearCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\StartPaymentCheckoutData;
use App\Modules\Payments\DTOs\StartPaymentCheckoutResult;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StartPaymentCheckoutAction
{
    public function __construct(
        private readonly GetCurrentCartAction $getCurrentCartAction,
        private readonly ClearCartAction $clearCartAction,
        private readonly CheckoutPreferenceGateway $checkoutPreferenceGateway,
    ) {}

    public function execute(StartPaymentCheckoutData $data): StartPaymentCheckoutResult
    {
        $this->guardContact($data);

        $cartItems = $this->getCurrentCartAction->execute();

        if ($cartItems !== []) {
            $this->guardCartItems($cartItems);
        }

        if ($data->existingPaymentId !== null) {
            $existing = Payment::query()
                ->with(['order.items'])
                ->find($data->existingPaymentId);

            if (
                $existing !== null
                && $existing->mercado_pago_preference_id !== null
                && $existing->mercado_pago_checkout_url !== null
                && $existing->order->status === OrderStatus::Pending
                && ($cartItems === [] || $this->cartMatchesOrder($cartItems, $existing->order))
            ) {
                Log::info('Reusing pending Mercado Pago checkout attempt.', [
                    'payment_id' => $existing->getKey(),
                    'order_id' => $existing->order_id,
                ]);

                if ($cartItems !== []) {
                    $this->clearCartAction->execute();
                }

                return new StartPaymentCheckoutResult(
                    $existing->order,
                    $existing,
                    new CheckoutPreferenceResult(
                        preferenceId: $existing->mercado_pago_preference_id,
                        publicKey: '',
                        checkoutUrl: $existing->mercado_pago_checkout_url,
                    ),
                );
            }
        }

        if ($cartItems === []) {
            throw new EmptyCart(__('general.errors.empty_cart'));
        }

        $payment = DB::transaction(function () use ($cartItems, $data): Payment {
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

                if ($product === null || $product->quantity < $cartItem['quantity']) {
                    throw new InsufficientStock(__('general.errors.insufficient_stock', [
                        'product_id' => $cartItem['product_id'],
                    ]));
                }
            }

            $order = Order::query()->create([
                'email' => $data->email,
                'whatsapp' => $data->whatsapp,
                'status' => OrderStatus::Pending,
            ]);

            $amountCents = 0;

            foreach ($cartItems as $cartItem) {
                /** @var Product $product */
                $product = $products->get($cartItem['product_id']);
                $quantity = (int) $cartItem['quantity'];
                $amountCents += (int) $product->price * $quantity;

                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                ]);

                $product->decrement('quantity', $quantity);
            }

            return Payment::query()->create([
                'order_id' => $order->getKey(),
                'external_reference' => 'payment-'.$order->getKey().'-'.Str::uuid()->toString(),
                'amount_cents' => $amountCents,
                'metadata' => [
                    'source' => 'checkout',
                ],
            ]);
        });

        try {
            $result = $this->createPreferenceForPayment($payment->load('order.items'));
        } catch (Throwable $exception) {
            $this->markPreferenceFailure($payment);

            report($exception);

            throw $exception;
        }

        $this->clearCartAction->execute();

        return $result;
    }

    private function createPreferenceForPayment(Payment $payment): StartPaymentCheckoutResult
    {
        $payment->loadMissing('order.items');
        $order = $payment->order;

        $preference = $this->checkoutPreferenceGateway->create(new CheckoutPreferenceData(
            email: $order->email,
            externalReference: $payment->external_reference,
            items: $order->items
                ->map(fn ($item): array => [
                    'id' => (string) $item->product_id,
                    'title' => (string) $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => round(((int) $item->unit_price) / 100, 2),
                    'currency_id' => 'BRL',
                ])
                ->values()
                ->all(),
            backUrls: [
                'success' => route('storefront.mercado-pago.success'),
                'failure' => route('storefront.mercado-pago.failure'),
                'pending' => route('storefront.mercado-pago.pending'),
            ],
        ));

        $payment->forceFill([
            'mercado_pago_preference_id' => $preference->preferenceId,
            'mercado_pago_checkout_url' => $preference->checkoutUrl,
        ])->save();

        return new StartPaymentCheckoutResult($order, $payment->refresh(), $preference);
    }

    private function markPreferenceFailure(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment = Payment::query()
                ->with('order.items')
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            $metadata = $payment->metadata ?? [];

            if (($metadata['stock_restored'] ?? false) !== true) {
                foreach ($payment->order->items as $item) {
                    Product::query()
                        ->whereKey($item->product_id)
                        ->increment('quantity', (int) $item->quantity);
                }

                $metadata['stock_restored'] = true;
                $metadata['stock_restored_reason'] = 'preference_creation_failed';
            }

            $payment->forceFill(['metadata' => $metadata])->save();
            $payment->order->forceFill(['status' => OrderStatus::Error])->save();
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string}>  $cartItems
     */
    private function cartMatchesOrder(array $cartItems, Order $order): bool
    {
        $order->loadMissing('items');

        return $this->normalizedCartItems($cartItems) === $order->items
            ->map(fn ($item): array => [
                'product_id' => (int) $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->unit_price,
            ])
            ->sortBy(fn (array $item): string => $this->normalizedItemSortKey($item))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string}>  $cartItems
     * @return array<int, array{product_id: int, quantity: int, unit_price: int}>
     */
    private function normalizedCartItems(array $cartItems): array
    {
        return collect($cartItems)
            ->map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) $item['unit_price'],
            ])
            ->sortBy(fn (array $item): string => $this->normalizedItemSortKey($item))
            ->values()
            ->all();
    }

    /**
     * @param  array{product_id: int, quantity: int, unit_price: int}  $item
     */
    private function normalizedItemSortKey(array $item): string
    {
        return sprintf('%010d:%010d:%010d', $item['product_id'], $item['quantity'], $item['unit_price']);
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

    private function guardContact(StartPaymentCheckoutData $data): void
    {
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidCheckoutContact(__('general.errors.invalid_order_email'));
        }

        $normalizedWhatsapp = preg_replace('/\D+/', '', $data->whatsapp) ?? '';

        if ($normalizedWhatsapp === '' || strlen($normalizedWhatsapp) < 10 || strlen($normalizedWhatsapp) > 15) {
            throw new InvalidCheckoutContact(__('general.errors.invalid_order_whatsapp'));
        }
    }
}
