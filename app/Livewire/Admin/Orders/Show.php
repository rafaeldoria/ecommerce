<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Queries\GetAdminOrderQuery;
use App\Support\MoneyFormatter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    use UsesLocalizedPageTitle;

    public int $order;

    public Order $foundOrder;

    public function mount(int $order, GetAdminOrderQuery $getAdminOrderQuery): void
    {
        $this->order = $order;
        $this->foundOrder = $getAdminOrderQuery->execute($order);
    }

    public function render()
    {
        $totalAmount = $this->foundOrder->items->sum(
            static fn ($item): int => $item->unit_price * $item->quantity
        );

        return $this->pageView('livewire.admin.orders.show', [
            'formattedTotalAmount' => MoneyFormatter::brlFromCents($totalAmount),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.orders.detail_title';
    }
}
