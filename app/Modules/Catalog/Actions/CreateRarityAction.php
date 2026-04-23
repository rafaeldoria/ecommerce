<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Rarity;

class CreateRarityAction
{
    public function execute(string $name): Rarity
    {
        return Rarity::query()->create([
            'name' => $name,
        ]);
    }
}
