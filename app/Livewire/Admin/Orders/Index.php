<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.admin.orders.index');
    }

    protected function titleKey(): string
    {
        return 'admin.orders.title';
    }
}
