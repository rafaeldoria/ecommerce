<?php

namespace App\Modules\Admin\Queries;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Orders\Models\Order;

class GetAdminDashboardStatsQuery
{
    /**
     * @return array{games: int, rarities: int, products: int, orders: int}
     */
    public function execute(): array
    {
        return [
            'games' => Game::query()->count(),
            'rarities' => Rarity::query()->count(),
            'products' => Product::query()->count(),
            'orders' => Order::query()->count(),
        ];
    }
}
