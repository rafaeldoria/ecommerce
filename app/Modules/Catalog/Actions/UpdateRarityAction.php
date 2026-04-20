<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateRarityAction
{
    public function execute(int $rarityId, string $name): Rarity
    {
        $rarity = Rarity::query()->find($rarityId);

        if ($rarity === null) {
            throw (new ModelNotFoundException)->setModel(Rarity::class, [$rarityId]);
        }

        $rarity->update([
            'name' => $name,
        ]);

        return $rarity->refresh();
    }
}
