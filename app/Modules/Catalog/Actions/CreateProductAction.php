<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DomainServices\ProductWriteRules;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\Models\Product;

class CreateProductAction
{
    public function __construct(
        private readonly ProductWriteRules $productWriteRules,
    ) {}

    public function execute(CreateProductData $data): Product
    {
        $this->productWriteRules->assertValid($data->price, $data->quantity, $data->gameId, $data->rarityId);

        return Product::query()->create([
            'name' => $data->name,
            'url_img' => $data->urlImg,
            'quantity' => $data->quantity,
            'price' => $data->price,
            'game_id' => $data->gameId,
            'rarity_id' => $data->rarityId,
        ]);
    }
}
