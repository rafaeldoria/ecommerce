<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class SearchCatalogProductsQuery
{
    public function execute(?int $gameId = null, ?int $rarityId = null): Collection
    {
        $query = Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->available()
            ->orderBy('name');

        if ($gameId !== null) {
            $query->where('game_id', $gameId);
        }

        if ($rarityId !== null) {
            $query->where('rarity_id', $rarityId);
        }

        return $query->get();
    }
}
