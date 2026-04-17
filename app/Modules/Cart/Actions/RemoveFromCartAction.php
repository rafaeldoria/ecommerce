<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;

class RemoveFromCartAction
{
    public function __construct(
        private readonly CartStore $cartStore,
    ) {}

    public function execute(int $productId): array
    {
        $items = array_values(array_filter(
            $this->cartStore->all(),
            static fn (array $item): bool => $item['product_id'] !== $productId,
        ));

        $this->cartStore->put($items);

        return $this->cartStore->all();
    }
}
