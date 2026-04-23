<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_price:int, product_name:string}>  $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalQuantity = 0;
        $totalAmount = 0;

        foreach ($this->resource as $item) {
            $totalQuantity += $item['quantity'];
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }

        return [
            'items' => array_values($this->resource),
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
        ];
    }
}
