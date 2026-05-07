<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ListFeaturedStorefrontProductsQuery
{
    public function execute(int $limit = 6): Collection
    {
        return Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->available()
            ->orderByDesc('price')
            ->limit($limit)
            ->get();
    }
}
