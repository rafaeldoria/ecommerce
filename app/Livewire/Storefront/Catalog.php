<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\ListStorefrontGamesQuery;
use App\Modules\Catalog\Queries\SearchCatalogProductsQuery;
use App\Support\MoneyFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Catalog extends Component
{
    use UsesLocalizedPageTitle;
    use WithPagination;

    /**
     * @var array<int, array{id: int, name: string, slug: string, count: int}>
     */
    public array $games = [];

    #[Url(as: 'game')]
    public ?string $selectedGameSlug = null;

    public int $totalProductCount = 0;

    public function mount(ListStorefrontGamesQuery $listStorefrontGamesQuery): void
    {
        $this->games = $listStorefrontGamesQuery->execute();

        $requestedGame = trim((string) request()->query('game', ''));
        $selectedGame = $this->resolveSelectedGame(collect($this->games), $requestedGame);

        $this->selectedGameSlug = $selectedGame['slug'] ?? null;

        if ($selectedGame === null) {
            $this->totalProductCount = 0;

            return;
        }
    }

    public function render(SearchCatalogProductsQuery $searchCatalogProductsQuery)
    {
        $selectedGame = $this->selectedGame();
        $products = null;

        if ($selectedGame !== null) {
            $products = $searchCatalogProductsQuery
                ->executePaginated(gameId: (int) $selectedGame['id'], perPage: 9)
                ->through(fn (Product $product): array => [
                    'id' => $product->getKey(),
                    'name' => $product->name,
                    'image_url' => $product->url_img,
                    'game' => $product->game->name,
                    'rarity' => $product->rarity->name,
                    'quantity' => $product->quantity,
                    'formatted_price' => MoneyFormatter::brlFromCents($product->price),
                    'route' => route('storefront.products.show', ['product' => $product]),
                ]);

            $this->totalProductCount = $products->total();
        }

        return $this->pageView('livewire.storefront.catalog', [
            'products' => $products,
        ]);
    }

    public function selectGame(string $gameSlug): void
    {
        $this->selectedGameSlug = $gameSlug;
        $this->resetPage();
    }

    private function selectedGame(): ?array
    {
        if ($this->selectedGameSlug === null) {
            return null;
        }

        return collect($this->games)
            ->first(fn (array $game): bool => $game['slug'] === $this->selectedGameSlug);
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.catalog.title';
    }

    private function resolveSelectedGame(Collection $games, string $requestedGame): ?array
    {
        if ($games->isEmpty()) {
            return null;
        }

        if ($requestedGame === '') {
            /** @var array{id: int, name: string, slug: string, count: int} $firstGame */
            $firstGame = $games->first();

            return $firstGame;
        }

        /** @var array{id: int, name: string, slug: string, count: int}|null $selectedGame */
        $selectedGame = $games->first(
            fn (array $game): bool => $game['slug'] === Str::slug($requestedGame)
        );

        if ($selectedGame !== null) {
            return $selectedGame;
        }

        /** @var array{id: int, name: string, slug: string, count: int} $firstGame */
        $firstGame = $games->first();

        return $firstGame;
    }
}
