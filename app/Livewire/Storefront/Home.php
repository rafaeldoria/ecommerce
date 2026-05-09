<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\ListFeaturedStorefrontProductsQuery;
use App\Modules\Catalog\Queries\ListStorefrontGamesQuery;
use App\Support\MoneyFormatter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    use UsesLocalizedPageTitle;

    public function render(
        ListStorefrontGamesQuery $listStorefrontGamesQuery,
        ListFeaturedStorefrontProductsQuery $listFeaturedStorefrontProductsQuery,
    )
    {
        $featuredProducts = $listFeaturedStorefrontProductsQuery->execute(6)
            ->map(fn (Product $product): array => [
                'id' => $product->getKey(),
                'name' => $product->name,
                'image_url' => $product->url_img,
                'game' => $product->game->name,
                'rarity' => $product->rarity->name,
                'quantity' => $product->quantity,
                'formatted_price' => MoneyFormatter::brlFromCents($product->price),
                'route' => route('storefront.products.show', ['product' => $product]),
            ])->values()->all();

        return $this->pageView('livewire.storefront.home', [
            'games' => $listStorefrontGamesQuery->execute(),
            'featuredProducts' => $featuredProducts,
        ]);
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.home.title';
    }
}
