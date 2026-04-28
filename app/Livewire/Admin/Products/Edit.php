<?php

namespace App\Livewire\Admin\Products;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\ProductImages\ProductImageStorage;
use App\Modules\Catalog\Queries\ListAdminGamesQuery;
use App\Modules\Catalog\Queries\ListAdminRaritiesQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Throwable;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use UsesLocalizedPageTitle;
    use WithFileUploads;

    public Product $product;

    public string $name = '';

    public mixed $image = null;

    public int|string|null $quantity = null;

    public int|string|null $price = null;

    public int|string|null $game_id = null;

    public int|string|null $rarity_id = null;

    public string $currentImageUrl = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->quantity = $product->quantity;
        $this->price = $product->price;
        $this->game_id = $product->game_id;
        $this->rarity_id = $product->rarity_id;
        $this->currentImageUrl = $product->url_img;
    }

    public function save(UpdateProductAction $updateProductAction)
    {
        $validated = $this->validate($this->rules());
        $newImageUrl = $this->storedProductImageUrl();
        $previousImageUrl = $newImageUrl === null ? null : $this->product->url_img;

        try {
            $updateProductAction->execute($this->product->getKey(), new UpdateProductData(
                name: (string) $validated['name'],
                urlImg: $newImageUrl,
                quantity: (int) $validated['quantity'],
                price: (int) $validated['price'],
                gameId: (int) $validated['game_id'],
                rarityId: (int) $validated['rarity_id'],
            ));
        } catch (Throwable $exception) {
            app(ProductImageStorage::class)->deleteIfOwned($newImageUrl);

            throw $exception;
        }

        if ($newImageUrl !== null) {
            app(ProductImageStorage::class)->deleteReplaced($previousImageUrl, $newImageUrl);
        }

        session()->flash('admin.status', __('admin.products.messages.updated'));

        return $this->redirectRoute('admin.products.index');
    }

    public function render(
        ListAdminGamesQuery $listAdminGamesQuery,
        ListAdminRaritiesQuery $listAdminRaritiesQuery,
    ) {
        return $this->pageView('livewire.admin.products.edit', [
            'games' => $listAdminGamesQuery->execute(),
            'rarities' => $listAdminRaritiesQuery->execute(),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.products.edit_title';
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Product::class, 'name')->ignore($this->product->getKey())->withoutTrashed(),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'game_id' => ['required', 'integer', Rule::exists(Game::class, 'id')->whereNull('deleted_at')],
            'rarity_id' => ['required', 'integer', Rule::exists(Rarity::class, 'id')->whereNull('deleted_at')],
        ];
    }

    private function storedProductImageUrl(): ?string
    {
        if (!$this->image instanceof UploadedFile) {
            return null;
        }

        return app(ProductImageStorage::class)->store($this->image);
    }
}
