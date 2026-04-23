<?php

namespace App\Modules\Catalog\DomainServices;

use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Rarity;

class ProductWriteRules
{
    public function assertValid(int $price, int $quantity, int $gameId, int $rarityId): void
    {
        if ($price < 0) {
            throw new InvalidProductData(__('general.errors.invalid_product_price'));
        }

        if ($quantity < 0) {
            throw new InvalidProductData(__('general.errors.invalid_product_quantity'));
        }

        if (!Game::query()->whereKey($gameId)->exists()) {
            throw new InvalidProductReference(__('general.errors.invalid_game_reference'));
        }

        if (!Rarity::query()->whereKey($rarityId)->exists()) {
            throw new InvalidProductReference(__('general.errors.invalid_rarity_reference'));
        }
    }
}
