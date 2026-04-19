<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreCartItemRequest;
use App\Http\Requests\Api\UpdateCartItemRequest;
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
        return $this->respond(fn (): JsonResponse => response()->json([
            'message' => __('general.api.cart.retrieved'),
            'data' => $this->cartPayload($this->getCurrentCartAction->execute()),
        ]));
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $items = $this->addToCartAction->execute(new AddToCartData(
                productId: (int) $request->validated('product_id'),
                quantity: (int) $request->validated('quantity'),
            ));

            return response()->json([
                'message' => __('general.api.cart.item_added'),
                'data' => $this->cartPayload($items),
            ], 201);
        });
    }

    public function update(UpdateCartItemRequest $request, int $product): JsonResponse
    {
        return $this->respond(function () use ($request, $product): JsonResponse {
            $items = $this->updateCartItemAction->execute(new UpdateCartItemData(
                productId: $product,
                quantity: (int) $request->validated('quantity'),
            ));

            return response()->json([
                'message' => __('general.api.cart.item_updated'),
                'data' => $this->cartPayload($items),
            ]);
        });
    }

    public function destroy(int $product): JsonResponse
    {
        return $this->respond(function () use ($product): JsonResponse {
            $items = $this->removeFromCartAction->execute($product);

            return response()->json([
                'message' => __('general.api.cart.item_removed'),
                'data' => $this->cartPayload($items),
            ]);
        });
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_price:int, product_name:string}>  $items
     */
    private function cartPayload(array $items): array
    {
        $totalQuantity = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $totalQuantity += $item['quantity'];
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }

        return [
            'items' => array_values($items),
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
        ];
    }
}
