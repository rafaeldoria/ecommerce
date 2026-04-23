<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Models\Game;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteGameAction
{
    public function execute(int $gameId): void
    {
        $game = Game::query()->find($gameId);

        if ($game === null) {
            throw (new ModelNotFoundException)->setModel(Game::class, [$gameId]);
        }

        if ($game->products()->withTrashed()->exists()) {
            throw new CatalogResourceInUse(__('general.errors.game_in_use'));
        }

        $game->delete();
    }
}
