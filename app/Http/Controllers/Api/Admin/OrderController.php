<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\AdminOrderResource;
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
        return response()->json([
            'message' => __('general.api.admin.orders.listed'),
            'data' => AdminOrderResource::collection($this->listAdminOrdersQuery->execute())->resolve(),
        ]);
    }

    public function show(int $order): JsonResponse
    {
        $foundOrder = $this->getAdminOrderQuery->execute($order);

        return response()->json([
            'message' => __('general.api.admin.orders.retrieved'),
            'data' => AdminOrderResource::make($foundOrder)->resolve(),
        ]);
    }
}
