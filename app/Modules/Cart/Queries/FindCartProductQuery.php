<?php

namespace App\Modules\Cart\Queries;

use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;

class FindCartProductQuery
{
    public function execute(int $productId): Product
    {
        $product = Product::query()
            ->whereKey($productId)
            ->whereNull('deleted_at')
            ->first();

        if ($product === null) {
            throw new InvalidProductReference(__('general.errors.invalid_product_reference'));
        }

        return $product;
    }
}
