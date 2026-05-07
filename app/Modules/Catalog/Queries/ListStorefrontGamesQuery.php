<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Game;
use Illuminate\Support\Str;

class ListStorefrontGamesQuery
{
    /**
     * @return array<int, array{id: int, name: string, slug: string, count: int}>
     */
    public function execute(): array
    {
        return Game::query()
            ->withCount([
                'products as available_products_count' => static fn ($query) => $query->available(),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Game $game): array => [
                'id' => $game->getKey(),
                'name' => $game->name,
                'slug' => Str::slug($game->name),
                'count' => (int) $game->available_products_count,
            ])
            ->values()
            ->all();
    }
}
