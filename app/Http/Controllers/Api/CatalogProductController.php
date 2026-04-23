<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ListCatalogProductsRequest;
use App\Http\Resources\Api\ProductResource;
use App\Modules\Catalog\Queries\ListCatalogFilterMetadataQuery;
use App\Modules\Catalog\Queries\SearchCatalogProductsQuery;
use Illuminate\Http\JsonResponse;

class CatalogProductController extends ApiController
{
    public function __construct(
        private readonly SearchCatalogProductsQuery $searchCatalogProductsQuery,
        private readonly ListCatalogFilterMetadataQuery $listCatalogFilterMetadataQuery,
    ) {}

    public function index(ListCatalogProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $products = $this->searchCatalogProductsQuery->execute(
            gameId: isset($validated['game_id']) ? (int) $validated['game_id'] : null,
            rarityId: isset($validated['rarity_id']) ? (int) $validated['rarity_id'] : null,
        );

        return response()->json([
            'message' => __('general.api.catalog.products_listed'),
            'data' => ProductResource::collection($products)->resolve(),
            'meta' => [
                'filters' => $this->listCatalogFilterMetadataQuery->execute(),
            ],
        ]);
    }
}
