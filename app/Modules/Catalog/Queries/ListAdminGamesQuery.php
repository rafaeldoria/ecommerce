<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListAdminGamesQuery
{
    public function execute(): Collection
    {
        return Game::query()
            ->orderBy('name')
            ->get();
    }

    public function executePaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Game::query()
            ->orderBy('name')
            ->paginate($perPage);
    }
}
