<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreCartItemRequest;
use App\Http\Requests\Api\UpdateCartItemRequest;
use App\Http\Resources\Api\CartResource;
use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Actions\RemoveFromCartAction;
use App\Modules\Cart\Actions\UpdateCartItemAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Cart\DTOs\UpdateCartItemData;
use Illuminate\Http\JsonResponse;

class CartController extends ApiController
{
    public function __construct(
        private readonly GetCurrentCartAction $getCurrentCartAction,
        private readonly AddToCartAction $addToCartAction,
        private readonly UpdateCartItemAction $updateCartItemAction,
        private readonly RemoveFromCartAction $removeFromCartAction,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'message' => __('general.api.cart.retrieved'),
            'data' => CartResource::make($this->getCurrentCartAction->execute())->resolve(),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $items = $this->addToCartAction->execute(new AddToCartData(
            productId: (int) $request->validated('product_id'),
            quantity: (int) $request->validated('quantity'),
        ));

        return response()->json([
            'message' => __('general.api.cart.item_added'),
            'data' => CartResource::make($items)->resolve(),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, int $product): JsonResponse
    {
        $items = $this->updateCartItemAction->execute(new UpdateCartItemData(
            productId: $product,
            quantity: (int) $request->validated('quantity'),
        ));

        return response()->json([
            'message' => __('general.api.cart.item_updated'),
            'data' => CartResource::make($items)->resolve(),
        ]);
    }

    public function destroy(int $product): JsonResponse
    {
        $items = $this->removeFromCartAction->execute($product);

        return response()->json([
            'message' => __('general.api.cart.item_removed'),
            'data' => CartResource::make($items)->resolve(),
        ]);
    }
}
