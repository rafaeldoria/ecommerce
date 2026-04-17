<?php

namespace App\Modules\Cart\Contracts;

interface CartStore
{
    /**
     * @return array<int, array{product_id:int, quantity:int, unit_price:int, product_name:string}>
     */
    public function all(): array;

    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_price:int, product_name:string}>  $items
     */
    public function put(array $items): void;

    public function clear(): void;
}
