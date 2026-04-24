<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetAvailableStorefrontProductQuery
{
    public function execute(int $productId): Product
    {
        $product = Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->available()
            ->find($productId);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        return $product;
    }
}
