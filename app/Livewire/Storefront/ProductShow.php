<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\GetAvailableStorefrontProductQuery;
use App\Support\MoneyFormatter;
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
        $this->formattedPrice = MoneyFormatter::brlFromCents($this->product->price);
    }

    public function addToCart(AddToCartAction $addToCartAction)
    {
        $addToCartAction->execute(new AddToCartData(
            productId: $this->product->getKey(),
            quantity: 1,
        ));

        session()->flash('cart.status', __('storefront.cart.messages.added'));

        return $this->redirectRoute('storefront.cart');
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
