<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Orders\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    use UsesLocalizedPageTitle;

    public array $stats = [];

    public function render()
    {
        $this->stats = [
            'games' => Game::query()->count(),
            'rarities' => Rarity::query()->count(),
            'products' => Product::query()->count(),
            'orders' => Order::query()->count(),
        ];

        return $this->pageView('livewire.admin.dashboard');
    }

    protected function titleKey(): string
    {
        return 'admin.dashboard.title';
    }
}
