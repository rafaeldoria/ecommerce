<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;
use App\Modules\Cart\DTOs\UpdateCartItemData;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;

class UpdateCartItemAction
{
    public function __construct(
        private readonly CartStore $cartStore,
    ) {}

    public function execute(UpdateCartItemData $data): array
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

        foreach ($items as $index => $item) {
            if ($item['product_id'] !== $product->getKey()) {
                continue;
            }

            $items[$index] = [
                'product_id' => $product->getKey(),
                'quantity' => $data->quantity,
                'unit_price' => $product->price,
                'product_name' => $product->name,
            ];

            $this->cartStore->put($items);

            return $this->cartStore->all();
        }

        throw new InvalidProductReference(__('general.errors.invalid_product_reference'));
    }

    private function guardQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidCartQuantity(__('general.errors.invalid_cart_quantity'));
        }
    }
}
