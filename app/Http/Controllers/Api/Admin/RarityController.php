<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StoreRarityRequest;
use App\Http\Requests\Api\Admin\UpdateRarityRequest;
use App\Modules\Catalog\Actions\CreateRarityAction;
use App\Modules\Catalog\Actions\DeleteRarityAction;
use App\Modules\Catalog\Actions\UpdateRarityAction;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\Queries\ListAdminRaritiesQuery;
use Illuminate\Http\JsonResponse;

class RarityController extends ApiController
{
    public function __construct(
        private readonly ListAdminRaritiesQuery $listAdminRaritiesQuery,
        private readonly CreateRarityAction $createRarityAction,
        private readonly UpdateRarityAction $updateRarityAction,
        private readonly DeleteRarityAction $deleteRarityAction,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.rarities.listed'),
            'data' => $this->listAdminRaritiesQuery->execute()->map(
                fn (Rarity $rarity): array => $this->payload($rarity)
            )->all(),
        ]));
    }

    public function store(StoreRarityRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $rarity = $this->createRarityAction->execute((string) $request->validated('name'));

            return response()->json([
                'message' => __('general.api.admin.rarities.created'),
                'data' => $this->payload($rarity),
            ], 201);
        });
    }

    public function show(Rarity $rarity): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.rarities.retrieved'),
            'data' => $this->payload($rarity),
        ]));
    }

    public function update(UpdateRarityRequest $request, int $rarity): JsonResponse
    {
        return $this->respond(function () use ($request, $rarity): JsonResponse {
            $updatedRarity = $this->updateRarityAction->execute($rarity, (string) $request->validated('name'));

            return response()->json([
                'message' => __('general.api.admin.rarities.updated'),
                'data' => $this->payload($updatedRarity),
            ]);
        });
    }

    public function destroy(int $rarity): JsonResponse
    {
        return $this->respond(function () use ($rarity): JsonResponse {
            $this->deleteRarityAction->execute($rarity);

            return response()->json([
                'message' => __('general.api.admin.rarities.deleted'),
            ]);
        });
    }

    private function payload(Rarity $rarity): array
    {
        return [
            'id' => $rarity->getKey(),
            'name' => $rarity->name,
        ];
    }
}
