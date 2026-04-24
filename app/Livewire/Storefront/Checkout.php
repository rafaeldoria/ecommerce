<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Checkout extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.storefront.checkout');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.checkout.title';
    }
}
