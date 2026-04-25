<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\GetAvailableStorefrontProductQuery;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductShow extends Component
{
    use UsesLocalizedPageTitle;

    public Product $product;

    public string $formattedPrice;

    public function mount(Product $product, GetAvailableStorefrontProductQuery $getAvailableStorefrontProductQuery): void
    {
        $this->product = $getAvailableStorefrontProductQuery->execute($product->getKey());
        $this->formattedPrice = Number::currency($this->product->price / 100, in: 'BRL', locale: app()->getLocale());
    }

    public function render()
    {
        return $this->pageView('livewire.storefront.product-show');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.product.title';
    }
}
