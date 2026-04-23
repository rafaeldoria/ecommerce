<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteProductAction
{
    public function execute(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        $product->delete();
    }
}
