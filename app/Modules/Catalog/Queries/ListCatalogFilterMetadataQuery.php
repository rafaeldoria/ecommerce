<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Rarity;

class ListCatalogFilterMetadataQuery
{
    /**
     * @return array{games: array<int, array{id:int, name:string}>, rarities: array<int, array{id:int, name:string}>}
     */
    public function execute(): array
    {
        return [
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
        ];
    }
}
