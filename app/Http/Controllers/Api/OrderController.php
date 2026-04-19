<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreOrderRequest;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends ApiController
{
    public function __construct(
        private readonly CreateOrderAction $createOrderAction,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $order = $this->createOrderAction->execute(new CreateOrderData(
                email: (string) $request->validated('email'),
                whatsapp: (string) $request->validated('whatsapp'),
            ));

            return response()->json([
                'message' => __('general.api.orders.created'),
                'data' => $this->orderPayload($order),
            ], 201);
        });
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->getKey(),
            'email' => $order->email,
            'whatsapp' => $order->whatsapp,
            'status' => $order->status,
            'items' => $order->items->map(static fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->all(),
        ];
    }
}
