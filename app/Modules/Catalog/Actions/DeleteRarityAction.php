<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteRarityAction
{
    public function execute(int $rarityId): void
    {
        $rarity = Rarity::query()->find($rarityId);

        if ($rarity === null) {
            throw (new ModelNotFoundException)->setModel(Rarity::class, [$rarityId]);
        }

        if ($rarity->products()->withTrashed()->exists()) {
            throw new CatalogResourceInUse(__('general.errors.rarity_in_use'));
        }

        $rarity->delete();
    }
}
