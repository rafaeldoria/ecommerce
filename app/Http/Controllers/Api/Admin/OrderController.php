<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Queries\GetAdminOrderQuery;
use App\Modules\Orders\Queries\ListAdminOrdersQuery;
use Illuminate\Http\JsonResponse;

class OrderController extends ApiController
{
    public function __construct(
        private readonly ListAdminOrdersQuery $listAdminOrdersQuery,
        private readonly GetAdminOrderQuery $getAdminOrderQuery,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.orders.listed'),
            'data' => $this->listAdminOrdersQuery->execute()->map(
                fn (Order $order): array => $this->payload($order)
            )->all(),
        ]));
    }

    public function show(int $order): JsonResponse
    {
        return $this->respond(function () use ($order): JsonResponse {
            $foundOrder = $this->getAdminOrderQuery->execute($order);

            return response()->json([
                'message' => __('general.api.admin.orders.retrieved'),
                'data' => $this->payload($foundOrder),
            ]);
        });
    }

    private function payload(Order $order): array
    {
        $totalAmount = $order->items->sum(static fn ($item): int => $item->unit_price * $item->quantity);

        return [
            'id' => $order->getKey(),
            'email' => $order->email,
            'whatsapp' => $order->whatsapp,
            'status' => $order->status,
            'created_at' => $order->created_at?->toISOString(),
            'total_amount' => $totalAmount,
            'items' => $order->items->map(static fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
