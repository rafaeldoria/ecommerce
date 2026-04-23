<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DomainServices\ProductWriteRules;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateProductAction
{
    public function __construct(
        private readonly ProductWriteRules $productWriteRules,
    ) {}

    public function execute(int $productId, UpdateProductData $data): Product
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        $this->productWriteRules->assertValid($data->price, $data->quantity, $data->gameId, $data->rarityId);

        $attributes = [
            'name' => $data->name,
            'quantity' => $data->quantity,
            'price' => $data->price,
            'game_id' => $data->gameId,
            'rarity_id' => $data->rarityId,
        ];

        if ($data->urlImg !== null) {
            $attributes['url_img'] = $data->urlImg;
        }

        $product->fill($attributes)->save();

        return $product->refresh();
    }
}
