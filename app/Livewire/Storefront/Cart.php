<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Actions\RemoveFromCartAction;
use App\Modules\Cart\Actions\UpdateCartItemAction;
use App\Modules\Cart\DTOs\UpdateCartItemData;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Product;
use App\Support\MoneyFormatter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Cart extends Component
{
    use UsesLocalizedPageTitle;

    /**
     * @var array<int, int|string>
     */
    public array $quantities = [];

    public function mount(GetCurrentCartAction $getCurrentCartAction): void
    {
        $this->syncQuantities($getCurrentCartAction->execute());
    }

    public function render(GetCurrentCartAction $getCurrentCartAction)
    {
        $items = $this->presentedItems($getCurrentCartAction->execute());

        return $this->pageView('livewire.storefront.cart', [
            'items' => $items,
            'total' => MoneyFormatter::brlFromCents((int) collect($items)->sum('subtotal_cents')),
        ]);
    }

    public function updateQuantity(int $productId, UpdateCartItemAction $updateCartItemAction): void
    {
        $quantity = (int) ($this->quantities[$productId] ?? 0);

        try {
            $items = $updateCartItemAction->execute(new UpdateCartItemData(
                productId: $productId,
                quantity: $quantity,
            ));
        } catch (InvalidCartQuantity|InvalidProductReference $exception) {
            $this->addError("quantities.{$productId}", $exception->getMessage());

            return;
        }

        $this->syncQuantities($items);
    }

    public function removeItem(int $productId, RemoveFromCartAction $removeFromCartAction): void
    {
        $items = $removeFromCartAction->execute($productId);
        $this->syncQuantities($items);
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.cart.title';
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string}>  $items
     * @return array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string, image_url: string, formatted_unit_price: string, formatted_subtotal: string, subtotal_cents: int}>
     */
    private function presentedItems(array $items): array
    {
        $products = Product::query()
            ->whereIn('id', collect($items)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        return collect($items)
            ->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id']);
                $subtotal = (int) $item['unit_price'] * (int) $item['quantity'];

                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'product_name' => (string) $item['product_name'],
                    'image_url' => $product?->url_img ?? '',
                    'formatted_unit_price' => MoneyFormatter::brlFromCents((int) $item['unit_price']),
                    'formatted_subtotal' => MoneyFormatter::brlFromCents($subtotal),
                    'subtotal_cents' => $subtotal,
                ];
            })->values()->all();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function syncQuantities(array $items): void
    {
        $this->quantities = collect($items)
            ->mapWithKeys(fn (array $item): array => [(int) $item['product_id'] => (int) $item['quantity']])
            ->all();
    }
}
