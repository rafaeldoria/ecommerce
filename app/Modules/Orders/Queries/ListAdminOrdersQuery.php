<?php

namespace App\Modules\Orders\Queries;

use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListAdminOrdersQuery
{
    public function execute(): Collection
    {
        return Order::query()
            ->with('items')
            ->latest()
            ->get();
    }

    public function executePaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }
}
