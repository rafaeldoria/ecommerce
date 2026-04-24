<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Queries\FindCartProductQuery;

class AddToCartAction
{
    public function __construct(
        private readonly CartStore $cartStore,
        private readonly FindCartProductQuery $findCartProductQuery,
    ) {}

    public function execute(AddToCartData $data): array
    {
        $this->guardQuantity($data->quantity);

        $product = $this->findCartProductQuery->execute($data->productId);

        $items = $this->cartStore->all();
        $existingIndex = $this->findItemIndex($items, $product->getKey());

        if ($existingIndex === null) {
            $items[] = [
                'product_id' => $product->getKey(),
                'quantity' => $data->quantity,
                'unit_price' => $product->price,
                'product_name' => $product->name,
            ];
            $this->cartStore->put($items);

            return $this->cartStore->all();
        }

        $items[$existingIndex]['quantity'] += $data->quantity;
        $items[$existingIndex]['unit_price'] = $product->price;
        $items[$existingIndex]['product_name'] = $product->name;

        $this->cartStore->put($items);

        return $this->cartStore->all();
    }

    private function guardQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidCartQuantity(__('general.errors.invalid_cart_quantity'));
        }
    }

    private function findItemIndex(array $items, int $productId): ?int
    {
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $productId) {
                return $index;
            }
        }

        return null;
    }
}
