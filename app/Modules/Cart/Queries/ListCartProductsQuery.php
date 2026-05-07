<?php

namespace App\Modules\Cart\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ListCartProductsQuery
{
    /**
     * @param  array<int, int>  $productIds
     */
    public function execute(array $productIds): Collection
    {
        return Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }
}
