<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ListAdminProductsQuery
{
    public function execute(): Collection
    {
        return Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->orderBy('name')
            ->get();
    }
}
