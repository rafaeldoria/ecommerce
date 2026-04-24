<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductShow extends Component
{
    use UsesLocalizedPageTitle;

    public Product $product;

    public string $formattedPrice;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->formattedPrice = Number::currency($product->price / 100, in: 'BRL', locale: app()->getLocale());
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
