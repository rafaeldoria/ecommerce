<?php

namespace App\Modules\Cart\DTOs;

readonly class UpdateCartItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
