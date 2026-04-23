<?php

namespace App\Http\Resources\Api;

use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'image_url' => $this->url_img,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'game' => [
                'id' => $this->game->getKey(),
                'name' => $this->game->name,
            ],
            'rarity' => [
                'id' => $this->rarity->getKey(),
                'name' => $this->rarity->name,
            ],
        ];
    }
}
