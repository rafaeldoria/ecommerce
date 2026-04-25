<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Orders\Queries\ListAdminOrdersQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use UsesLocalizedPageTitle;

    public function render(ListAdminOrdersQuery $listAdminOrdersQuery)
    {
        return $this->pageView('livewire.admin.orders.index', [
            'orders' => $listAdminOrdersQuery->execute(),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.orders.title';
    }
}
