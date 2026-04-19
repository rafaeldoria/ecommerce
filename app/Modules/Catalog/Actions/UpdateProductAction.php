<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateProductAction
{
    public function execute(int $productId, UpdateProductData $data): Product
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        $this->guardData($data->price, $data->quantity);
        $this->guardReferences($data->gameId, $data->rarityId);

        $product->fill([
            'name' => $data->name,
            'url_img' => $data->urlImg,
            'quantity' => $data->quantity,
            'price' => $data->price,
            'game_id' => $data->gameId,
            'rarity_id' => $data->rarityId,
        ])->save();

        return $product->refresh();
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
