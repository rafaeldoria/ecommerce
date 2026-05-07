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
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'total_amount' => $totalAmount,
            'latest_payment' => $this->when($this->relationLoaded('payments'), function (): ?array {
                $payment = $this->payments->first();

                if ($payment === null) {
                    return null;
                }

                $webhookRequest = $payment->relationLoaded('webhookRequests')
                    ? $payment->webhookRequests->first()
                    : null;

                return [
                    'id' => $payment->getKey(),
                    'provider' => $payment->provider,
                    'status' => $payment->status,
                    'provider_payment_id' => $payment->provider_payment_id,
                    'provider_status' => $payment->provider_status,
                    'provider_status_detail' => $payment->provider_status_detail,
                    'last_provider_update_at' => $payment->updated_at?->toISOString(),
                    'webhook_journal' => $webhookRequest === null ? null : [
                        'id' => $webhookRequest->getKey(),
                        'processing_status' => $webhookRequest->processing_status,
                        'data_id' => $webhookRequest->data_id,
                        'provider_payment_id' => $webhookRequest->provider_payment_id,
                        'received_at' => $webhookRequest->received_at?->toISOString(),
                    ],
                ];
            }),
            'items' => $this->items->map(static fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
