<?php

namespace App\Modules\Catalog\DTOs;

readonly class CreateProductData
{
    public function __construct(
        public string $name,
        public string $urlImg,
        public int $quantity,
        public int $price,
        public int $gameId,
        public int $rarityId,
    ) {}
}
