<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ListCatalogProductsRequest;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\Queries\SearchCatalogProductsQuery;
use Illuminate\Http\JsonResponse;

class CatalogProductController extends ApiController
{
    public function __construct(
        private readonly SearchCatalogProductsQuery $searchCatalogProductsQuery,
    ) {}

    public function index(ListCatalogProductsRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $validated = $request->validated();

            $products = $this->searchCatalogProductsQuery->execute(
                gameId: isset($validated['game_id']) ? (int) $validated['game_id'] : null,
                rarityId: isset($validated['rarity_id']) ? (int) $validated['rarity_id'] : null,
            );

            return response()->json([
                'message' => __('general.api.catalog.products_listed'),
                'data' => $products->map(static fn ($product): array => [
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
                ])->values()->all(),
                'meta' => [
                    'filters' => [
                        'games' => Game::query()
                            ->orderBy('name')
                            ->get(['id', 'name'])
                            ->map(static fn (Game $game): array => [
                                'id' => $game->getKey(),
                                'name' => $game->name,
                            ])->all(),
                        'rarities' => Rarity::query()
                            ->orderBy('name')
                            ->get(['id', 'name'])
                            ->map(static fn (Rarity $rarity): array => [
                                'id' => $rarity->getKey(),
                                'name' => $rarity->name,
                            ])->all(),
                    ],
                ],
            ]);
        });
    }
}
