<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StoreGameRequest;
use App\Http\Requests\Api\Admin\UpdateGameRequest;
use App\Modules\Catalog\Actions\CreateGameAction;
use App\Modules\Catalog\Actions\DeleteGameAction;
use App\Modules\Catalog\Actions\UpdateGameAction;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Queries\ListAdminGamesQuery;
use Illuminate\Http\JsonResponse;

class GameController extends ApiController
{
    public function __construct(
        private readonly ListAdminGamesQuery $listAdminGamesQuery,
        private readonly CreateGameAction $createGameAction,
        private readonly UpdateGameAction $updateGameAction,
        private readonly DeleteGameAction $deleteGameAction,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.games.listed'),
            'data' => $this->listAdminGamesQuery->execute()->map(
                fn (Game $game): array => $this->payload($game)
            )->all(),
        ]));
    }

    public function store(StoreGameRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $game = $this->createGameAction->execute((string) $request->validated('name'));

            return response()->json([
                'message' => __('general.api.admin.games.created'),
                'data' => $this->payload($game),
            ], 201);
        });
    }

    public function show(Game $game): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.games.retrieved'),
            'data' => $this->payload($game),
        ]));
    }

    public function update(UpdateGameRequest $request, int $game): JsonResponse
    {
        return $this->respond(function () use ($request, $game): JsonResponse {
            $updatedGame = $this->updateGameAction->execute($game, (string) $request->validated('name'));

            return response()->json([
                'message' => __('general.api.admin.games.updated'),
                'data' => $this->payload($updatedGame),
            ]);
        });
    }

    public function destroy(int $game): JsonResponse
    {
        return $this->respond(function () use ($game): JsonResponse {
            $this->deleteGameAction->execute($game);

            return response()->json([
                'message' => __('general.api.admin.games.deleted'),
            ]);
        });
    }

    private function payload(Game $game): array
    {
        return [
            'id' => $game->getKey(),
            'name' => $game->name,
        ];
    }
}
