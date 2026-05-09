<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\AdminOrderResource;
use App\Modules\Orders\Queries\GetAdminOrderQuery;
use App\Modules\Orders\Queries\ListAdminOrdersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        private readonly ListAdminOrdersQuery $listAdminOrdersQuery,
        private readonly GetAdminOrderQuery $getAdminOrderQuery,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->listAdminOrdersQuery->executePaginated(
            perPage: $this->perPage($request),
        );
        $payload = AdminOrderResource::collection($orders)->response()->getData(true);

        return response()->json([
            'message' => __('general.api.admin.orders.listed'),
            ...$payload,
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

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->integer('per_page', 15);

        return max(1, min($perPage, 100));
    }
}
