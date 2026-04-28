<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
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
            $this->totalProductCount = 0;

            return;
        }
    }

    public function render()
    {
        $selectedGame = $this->selectedGame();
        $products = null;

        if ($selectedGame !== null) {
            $products = Product::query()
                ->with(['game:id,name', 'rarity:id,name'])
                ->available()
                ->where('game_id', $selectedGame->getKey())
                ->orderBy('name')
                ->paginate(9)
                ->withQueryString()
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

    private function selectedGame(): ?Game
    {
        if ($this->selectedGameSlug === null) {
            return null;
        }

        return Game::query()
            ->orderBy('name')
            ->get()
            ->first(fn (Game $game): bool => Str::slug($game->name) === $this->selectedGameSlug);
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
