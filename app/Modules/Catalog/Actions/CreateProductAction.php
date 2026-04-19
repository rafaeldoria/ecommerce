<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;

class CreateProductAction
{
    public function execute(CreateProductData $data): Product
    {
        $this->guardData($data->price, $data->quantity);
        $this->guardReferences($data->gameId, $data->rarityId);

        return Product::query()->create([
            'name' => $data->name,
            'url_img' => $data->urlImg,
            'quantity' => $data->quantity,
            'price' => $data->price,
            'game_id' => $data->gameId,
            'rarity_id' => $data->rarityId,
        ]);
    }

    private function guardData(int $price, int $quantity): void
    {
        if ($price < 0) {
            throw new InvalidProductData(__('general.errors.invalid_product_price'));
        }

        if ($quantity < 0) {
            throw new InvalidProductData(__('general.errors.invalid_product_quantity'));
        }
    }

    private function guardReferences(int $gameId, int $rarityId): void
    {
        if (!Game::query()->whereKey($gameId)->exists()) {
            throw new InvalidProductReference(__('general.errors.invalid_game_reference'));
        }

        if (!Rarity::query()->whereKey($rarityId)->exists()) {
            throw new InvalidProductReference(__('general.errors.invalid_rarity_reference'));
        }
    }
}
