<?php

namespace App\Http\Resources\Api;

use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AdminOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalAmount = $this->items->sum(static fn ($item): int => $item->unit_price * $item->quantity);

        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
            'total_amount' => $totalAmount,
            'items' => $this->items->map(static fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
