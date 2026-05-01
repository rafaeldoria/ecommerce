<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\CreateCheckoutPreferenceData;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use Illuminate\Support\Str;

class CreateCheckoutPreferenceAction
{
    public function __construct(
        private readonly GetCurrentCartAction $getCurrentCartAction,
        private readonly CheckoutPreferenceGateway $checkoutPreferenceGateway,
    ) {}

    public function execute(CreateCheckoutPreferenceData $data): CheckoutPreferenceResult
    {
        $this->guardContact($data);

        $cartItems = $this->getCurrentCartAction->execute();

        if ($cartItems === []) {
            throw new EmptyCart(__('general.errors.empty_cart'));
        }

        $this->guardCartItems($cartItems);

        return $this->checkoutPreferenceGateway->create(new CheckoutPreferenceData(
            email: $data->email,
            externalReference: 'cart-test-'.Str::uuid()->toString(),
            items: $this->mercadoPagoItems($cartItems),
            backUrls: [
                'success' => route('storefront.mercado-pago.success'),
                'failure' => route('storefront.mercado-pago.failure'),
                'pending' => route('storefront.mercado-pago.pending'),
            ],
        ));
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string}>  $cartItems
     * @return array<int, array{id: string, title: string, quantity: int, unit_price: float, currency_id: string}>
     */
    private function mercadoPagoItems(array $cartItems): array
    {
        return collect($cartItems)
            ->map(fn (array $item): array => [
                'id' => (string) $item['product_id'],
                'title' => (string) $item['product_name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => round(((int) $item['unit_price']) / 100, 2),
                'currency_id' => 'BRL',
            ])
            ->values()
            ->all();
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

    private function guardContact(CreateCheckoutPreferenceData $data): void
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
