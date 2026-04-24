<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductShow extends Component
{
    use UsesLocalizedPageTitle;

    public string $product;

    public function mount(string $product): void
    {
        $this->product = $product;
    }

    public function render()
    {
        return view('livewire.storefront.product-show');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.product.title';
    }
}
