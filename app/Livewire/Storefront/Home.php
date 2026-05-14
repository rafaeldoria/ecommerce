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
    ) {
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
            'ratingTestimonials' => $this->ratingTestimonials($featuredProducts),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $featuredProducts
     * @return array<int, array<string, mixed>>
     */
    private function ratingTestimonials(array $featuredProducts): array
    {
        $copies = trans('storefront.home.ratings.cards');

        if (!is_array($copies)) {
            return [];
        }

        return collect($featuredProducts)
            ->take(3)
            ->values()
            ->map(function (array $product, int $index) use ($copies): array {
                $copy = $copies[$index] ?? [];

                return [
                    'reviewer_name' => $copy['reviewer_name'] ?? '',
                    'reviewed_at' => $copy['reviewed_at'] ?? '',
                    'comment' => $copy['comment'] ?? '',
                    'rating' => 5,
                    'product_name' => $product['name'],
                    'product_image_url' => $product['image_url'],
                    'product_route' => $product['route'],
                ];
            })
            ->all();
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.home.title';
    }
}
