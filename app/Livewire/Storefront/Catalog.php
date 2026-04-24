<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Catalog extends Component
{
    use UsesLocalizedPageTitle;

    /**
     * @var array<int, array{id: int, name: string, slug: string, count: int}>
     */
    public array $games = [];

    /**
     * @var array<int, array{id: int, name: string, image_url: string, game: string, rarity: string, quantity: int, formatted_price: string, route: string}>
     */
    public array $products = [];

    public ?string $selectedGameSlug = null;

    public int $visibleProductCount = 0;

    public function mount(): void
    {
        $games = Game::query()
            ->withCount([
                'products as available_products_count' => static fn ($query) => $query->available(),
            ])
            ->orderBy('name')
            ->get();

        $this->games = $games
            ->map(fn (Game $game): array => [
                'id' => $game->getKey(),
                'name' => $game->name,
                'slug' => Str::slug($game->name),
                'count' => (int) $game->available_products_count,
            ])->values()->all();

        $requestedGame = trim((string) request()->query('game', ''));
        $selectedGame = $this->resolveSelectedGame($games, $requestedGame);

        $this->selectedGameSlug = $selectedGame !== null ? Str::slug($selectedGame->name) : null;

        if ($selectedGame === null) {
            $this->products = [];
            $this->visibleProductCount = 0;

            return;
        }

        $products = Product::query()
            ->with(['game:id,name', 'rarity:id,name'])
            ->available()
            ->where('game_id', $selectedGame->getKey())
            ->orderBy('name')
            ->get();

        $this->products = $products
            ->map(fn (Product $product): array => [
                'id' => $product->getKey(),
                'name' => $product->name,
                'image_url' => $product->url_img,
                'game' => $product->game->name,
                'rarity' => $product->rarity->name,
                'quantity' => $product->quantity,
                'formatted_price' => Number::currency($product->price / 100, in: 'BRL', locale: app()->getLocale()),
                'route' => route('storefront.products.show', ['product' => $product]),
            ])->values()->all();

        $this->visibleProductCount = count($this->products);
    }

    public function render()
    {
        return $this->pageView('livewire.storefront.catalog');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.catalog.title';
    }

    private function resolveSelectedGame(Collection $games, string $requestedGame): ?Game
    {
        if ($games->isEmpty()) {
            return null;
        }

        if ($requestedGame === '') {
            /** @var Game $firstGame */
            $firstGame = $games->first();

            return $firstGame;
        }

        /** @var ?Game $selectedGame */
        $selectedGame = $games->first(
            fn (Game $game): bool => Str::slug($game->name) === Str::slug($requestedGame)
        );

        if ($selectedGame !== null) {
            return $selectedGame;
        }

        /** @var Game $firstGame */
        $firstGame = $games->first();

        return $firstGame;
    }
}
