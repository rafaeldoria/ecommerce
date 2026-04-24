<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    use UsesLocalizedPageTitle;

    public int $order;

    public function mount(int $order): void
    {
        $this->order = $order;
    }

    public function render()
    {
        return view('livewire.admin.orders.show');
    }

    protected function titleKey(): string
    {
        return 'admin.orders.detail_title';
    }
}
