<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Rarity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListAdminRaritiesQuery
{
    public function execute(): Collection
    {
        return Rarity::query()
            ->orderBy('name')
            ->get();
    }

    public function executePaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Rarity::query()
            ->orderBy('name')
            ->paginate($perPage);
    }
}
