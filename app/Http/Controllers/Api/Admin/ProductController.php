<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DeleteProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\ProductImages\ProductImageStorage;
use App\Modules\Catalog\Queries\ListAdminProductsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class ProductController extends ApiController
{
    public function __construct(
        private readonly ListAdminProductsQuery $listAdminProductsQuery,
        private readonly CreateProductAction $createProductAction,
        private readonly UpdateProductAction $updateProductAction,
        private readonly DeleteProductAction $deleteProductAction,
        private readonly ProductImageStorage $productImageStorage,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => __('general.api.admin.products.listed'),
            'data' => ProductResource::collection($this->listAdminProductsQuery->execute())->resolve(),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $imageUrl = $this->storeProductImage($request->file('image'));

        try {
            $product = $this->createProductAction->execute(new CreateProductData(
                name: (string) $validated['name'],
                urlImg: $imageUrl,
                quantity: (int) $validated['quantity'],
                price: (int) $validated['price'],
                gameId: (int) $validated['game_id'],
                rarityId: (int) $validated['rarity_id'],
            ));
        } catch (Throwable $exception) {
            $this->productImageStorage->deleteIfOwned($imageUrl);

            throw $exception;
        }

        $product->load(['game:id,name', 'rarity:id,name']);

        return response()->json([
            'message' => __('general.api.admin.products.created'),
            'data' => ProductResource::make($product)->resolve(),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'message' => __('general.api.admin.products.retrieved'),
            'data' => ProductResource::make($product->loadMissing(['game:id,name', 'rarity:id,name']))->resolve(),
        ]);
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $validated = $request->validated();
        $newImageUrl = $this->optionalStoredProductImageUrl($request->file('image'));
        $previousImageUrl = $newImageUrl === null
            ? null
            : Product::query()->whereKey($product)->value('url_img');

        try {
            $updatedProduct = $this->updateProductAction->execute($product, new UpdateProductData(
                name: (string) $validated['name'],
                urlImg: $newImageUrl,
                quantity: (int) $validated['quantity'],
                price: (int) $validated['price'],
                gameId: (int) $validated['game_id'],
                rarityId: (int) $validated['rarity_id'],
            ));
        } catch (Throwable $exception) {
            $this->productImageStorage->deleteIfOwned($newImageUrl);

            throw $exception;
        }

        if ($newImageUrl !== null) {
            $this->productImageStorage->deleteReplaced($previousImageUrl, $newImageUrl);
        }

        $updatedProduct->load(['game:id,name', 'rarity:id,name']);

        return response()->json([
            'message' => __('general.api.admin.products.updated'),
            'data' => ProductResource::make($updatedProduct)->resolve(),
        ]);
    }

    public function destroy(int $product): JsonResponse
    {
        $this->deleteProductAction->execute($product);

        return response()->json([
            'message' => __('general.api.admin.products.deleted'),
        ]);
    }

    private function optionalStoredProductImageUrl(mixed $image): ?string
    {
        if (!$image instanceof UploadedFile) {
            return null;
        }

        return $this->storeProductImage($image);
    }

    private function storeProductImage(UploadedFile $image): string
    {
        return $this->productImageStorage->store($image);
    }
}
