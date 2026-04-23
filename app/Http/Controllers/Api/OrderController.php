<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\Api\OrderResource;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use Illuminate\Http\JsonResponse;

class OrderController extends ApiController
{
    public function __construct(
        private readonly CreateOrderAction $createOrderAction,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrderAction->execute(new CreateOrderData(
            email: (string) $request->validated('email'),
            whatsapp: (string) $request->validated('whatsapp'),
        ));

        return response()->json([
            'message' => __('general.api.orders.created'),
            'data' => OrderResource::make($order)->resolve(),
        ], 201);
    }
}
