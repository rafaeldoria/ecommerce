<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Game;
use Illuminate\Database\Eloquent\Collection;

class ListAdminGamesQuery
{
    public function execute(): Collection
    {
        return Game::query()
            ->orderBy('name')
            ->get();
    }
}
