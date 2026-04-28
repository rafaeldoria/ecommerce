<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DeleteProductAction;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\ProductImages\ProductImageStorage;
use App\Modules\Catalog\Queries\ListAdminGamesQuery;
use App\Modules\Catalog\Queries\ListAdminProductsQuery;
use App\Modules\Catalog\Queries\ListAdminRaritiesQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.admin')]
class Products extends Component
{
    use UsesLocalizedPageTitle;
    use WithFileUploads;
    use WithPagination;

    public string $name = '';

    public mixed $image = null;

    public int|string|null $quantity = null;

    public int|string|null $price = null;

    public int|string|null $game_id = null;

    public int|string|null $rarity_id = null;

    public ?int $confirmingDeleteProductId = null;

    public bool $isFormOpen = false;

    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    public function render(
        ListAdminProductsQuery $listAdminProductsQuery,
        ListAdminGamesQuery $listAdminGamesQuery,
        ListAdminRaritiesQuery $listAdminRaritiesQuery,
    ) {
        return $this->pageView('livewire.admin.products', [
            'products' => $listAdminProductsQuery->executePaginated(10),
            'games' => $listAdminGamesQuery->execute(),
            'rarities' => $listAdminRaritiesQuery->execute(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());
        $newImageUrl = $this->storedProductImageUrl();

        $this->createProduct($validated, (string) $newImageUrl);
        $this->flashStatus(__('admin.products.messages.created'));

        $this->resetForm();
        $this->resetPage();
    }

    public function beginCreate(): void
    {
        $this->resetForm();
        $this->clearStatus();
        $this->isFormOpen = true;
    }

    public function confirmDelete(int $productId): void
    {
        $this->confirmingDeleteProductId = $productId;
        $this->clearStatus();
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteProductId === null) {
            return;
        }

        app(DeleteProductAction::class)->execute($this->confirmingDeleteProductId);

        $this->flashStatus(__('admin.products.messages.deleted'));
        $this->resetForm();
        $this->resetPage();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->clearStatus();
    }

    protected function titleKey(): string
    {
        return 'admin.products.title';
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
                Rule::unique(Product::class, 'name')->withoutTrashed(),
            ],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'game_id' => ['required', 'integer', Rule::exists(Game::class, 'id')->whereNull('deleted_at')],
            'rarity_id' => ['required', 'integer', Rule::exists(Rarity::class, 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @param  array{name: string, quantity: int|string, price: int|string, game_id: int|string, rarity_id: int|string}  $validated
     */
    private function createProduct(array $validated, string $newImageUrl): void
    {
        try {
            app(CreateProductAction::class)->execute(new CreateProductData(
                name: $validated['name'],
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
    }

    private function storedProductImageUrl(): ?string
    {
        if (!$this->image instanceof UploadedFile) {
            return null;
        }

        return app(ProductImageStorage::class)->store($this->image);
    }

    private function resetForm(): void
    {
        $this->reset(
            'name',
            'image',
            'quantity',
            'price',
            'game_id',
            'rarity_id',
            'confirmingDeleteProductId',
            'isFormOpen',
        );
        $this->resetValidation();
    }

    private function flashStatus(string $message, string $tone = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusTone = $tone;
    }

    private function clearStatus(): void
    {
        $this->statusMessage = null;
        $this->statusTone = 'success';
    }
}
