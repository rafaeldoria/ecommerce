<?php

namespace App\Modules\Orders\Queries;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetAdminOrderQuery
{
    public function execute(int $orderId): Order
    {
        $order = Order::query()
            ->with(['items', 'payment'])
            ->find($orderId);

        if ($order === null) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }

        return $order;
    }
}
