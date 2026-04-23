<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DeleteProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\ListAdminProductsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductController extends ApiController
{
    private const string PRODUCT_IMAGE_DIRECTORY = 'products';

    public function __construct(
        private readonly ListAdminProductsQuery $listAdminProductsQuery,
        private readonly CreateProductAction $createProductAction,
        private readonly UpdateProductAction $updateProductAction,
        private readonly DeleteProductAction $deleteProductAction,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.products.listed'),
            'data' => $this->listAdminProductsQuery->execute()->map(
                fn (Product $product): array => $this->payload($product)
            )->all(),
        ]));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $validated = $request->validated();

            $product = $this->createProductAction->execute(new CreateProductData(
                name: (string) $validated['name'],
                urlImg: $this->storeProductImage($request->file('image')),
                quantity: (int) $validated['quantity'],
                price: (int) $validated['price'],
                gameId: (int) $validated['game_id'],
                rarityId: (int) $validated['rarity_id'],
            ));

            $product->load(['game:id,name', 'rarity:id,name']);

            return response()->json([
                'message' => __('general.api.admin.products.created'),
                'data' => $this->payload($product),
            ], 201);
        });
    }

    public function show(Product $product): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.products.retrieved'),
            'data' => $this->payload($product->loadMissing(['game:id,name', 'rarity:id,name'])),
        ]));
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        return $this->respond(function () use ($request, $product): JsonResponse {
            $validated = $request->validated();

            $updatedProduct = $this->updateProductAction->execute($product, new UpdateProductData(
                name: (string) $validated['name'],
                urlImg: $this->optionalStoredProductImageUrl($request->file('image')),
                quantity: (int) $validated['quantity'],
                price: (int) $validated['price'],
                gameId: (int) $validated['game_id'],
                rarityId: (int) $validated['rarity_id'],
            ));

            $updatedProduct->load(['game:id,name', 'rarity:id,name']);

            return response()->json([
                'message' => __('general.api.admin.products.updated'),
                'data' => $this->payload($updatedProduct),
            ]);
        });
    }

    public function destroy(int $product): JsonResponse
    {
        return $this->respond(function () use ($product): JsonResponse {
            $this->deleteProductAction->execute($product);

            return response()->json([
                'message' => __('general.api.admin.products.deleted'),
            ]);
        });
    }

    private function payload(Product $product): array
    {
        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'image_url' => $product->url_img,
            'quantity' => $product->quantity,
            'price' => $product->price,
            'game' => [
                'id' => $product->game->getKey(),
                'name' => $product->game->name,
            ],
            'rarity' => [
                'id' => $product->rarity->getKey(),
                'name' => $product->rarity->name,
            ],
        ];
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
        $path = $image->store(self::PRODUCT_IMAGE_DIRECTORY, 'public');

        if ($path === false) {
            throw new RuntimeException(__('general.errors.product_image_storage_failed'));
        }

        return Storage::disk('public')->url($path);
    }
}
