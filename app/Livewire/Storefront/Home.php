<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Support\MoneyFormatter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        $games = Game::query()
            ->withCount([
                'products as available_products_count' => static fn ($query) => $query->available(),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Game $game): array => [
                'name' => $game->name,
                'slug' => Str::slug($game->name),
                'count' => (int) $game->available_products_count,
            ])->values()->all();

        $featuredProducts = Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->available()
            ->orderByDesc('price')
            ->limit(6)
            ->get()
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
            'games' => $games,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.home.title';
    }
}
