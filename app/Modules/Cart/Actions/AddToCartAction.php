<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;

class AddToCartAction
{
    public function __construct(
        private readonly CartStore $cartStore,
    ) {}

    public function execute(AddToCartData $data): array
    {
        $this->guardQuantity($data->quantity);

        $product = Product::query()
            ->whereKey($data->productId)
            ->whereNull('deleted_at')
            ->first();

        if ($product === null) {
            throw new InvalidProductReference(__('general.errors.invalid_product_reference'));
        }

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
