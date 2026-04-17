<?php

namespace App\Modules\Cart\DTOs;

readonly class AddToCartData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
