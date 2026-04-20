<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Game;

class CreateGameAction
{
    public function execute(string $name): Game
    {
        return Game::query()->create([
            'name' => $name,
        ]);
    }
}
