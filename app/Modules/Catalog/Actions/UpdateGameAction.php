<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Game;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateGameAction
{
    public function execute(int $gameId, string $name): Game
    {
        $game = Game::query()->find($gameId);

        if ($game === null) {
            throw (new ModelNotFoundException)->setModel(Game::class, [$gameId]);
        }

        $game->update([
            'name' => $name,
        ]);

        return $game->refresh();
    }
}
