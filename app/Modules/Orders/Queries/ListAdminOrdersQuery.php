<?php

namespace App\Modules\Orders\Queries;

use App\Modules\Orders\Models\Order;
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
}
