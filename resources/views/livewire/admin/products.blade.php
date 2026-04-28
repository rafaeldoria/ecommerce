@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240"><rect width="320" height="240" fill="#111827"/><circle cx="250" cy="58" r="34" fill="#34d399" fill-opacity="0.35"/><text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="#e5e7eb" font-family="sans-serif" font-size="18">GR-Shop</text></svg>'))

<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">{{ __('admin.products.title') }}</h1>
            <p class="mt-3 text-zinc-300">{{ __('admin.products.summary') }}</p>
        </div>

        <button
            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
            type="button"
            wire:click="beginCreate"
            wire:loading.attr="disabled"
            wire:target="beginCreate"
        >
            {{ __('admin.products.create') }}
        </button>
    </div>

    @if (session('admin.status'))
        <div class="rounded-lg border border-emerald-500/50 bg-emerald-950/40 px-4 py-3 text-emerald-100" role="status">
            <p class="text-sm font-semibold">{{ __('admin.shared.status_success') }}</p>
            <p class="mt-1 text-sm">{{ session('admin.status') }}</p>
        </div>
    @endif

    @if ($statusMessage !== null)
        <div
            class="{{ $statusTone === 'danger' ? 'border-red-500/50 bg-red-950/40 text-red-100' : 'border-emerald-500/50 bg-emerald-950/40 text-emerald-100' }} rounded-lg border px-4 py-3"
            role="status"
        >
            <p class="text-sm font-semibold">
                {{ $statusTone === 'danger' ? __('admin.shared.status_danger') : __('admin.shared.status_success') }}
            </p>
            <p class="mt-1 text-sm">{{ $statusMessage }}</p>
        </div>
    @endif

    @if ($isFormOpen)
        <form class="space-y-5 rounded-lg border border-zinc-800 bg-zinc-900/70 p-5" wire:submit="save">
            <h2 class="text-lg font-semibold text-white">
                {{ __('admin.products.create_title') }}
            </h2>

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-name">{{ __('admin.products.name_label') }}</label>
                    <input
                        id="product-name"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition placeholder:text-zinc-600 focus:border-emerald-400"
                        type="text"
                        wire:model="name"
                        placeholder="{{ __('admin.products.name_placeholder') }}"
                        autocomplete="off"
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-image">{{ __('admin.products.image_label') }}</label>
                    <input
                        id="product-image"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-zinc-100 hover:file:bg-zinc-700"
                        type="file"
                        wire:model="image"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <p class="mt-2 text-sm text-zinc-400">
                        {{ __('admin.products.image_help_create') }}
                    </p>
                    @error('image')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-quantity">{{ __('admin.products.quantity_label') }}</label>
                    <input
                        id="product-quantity"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400"
                        type="number"
                        min="0"
                        wire:model="quantity"
                    >
                    @error('quantity')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-price">{{ __('admin.products.price_label') }}</label>
                    <input
                        id="product-price"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400"
                        type="number"
                        min="0"
                        wire:model="price"
                    >
                    @error('price')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-game">{{ __('admin.products.game_label') }}</label>
                    <select
                        id="product-game"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400"
                        wire:model="game_id"
                    >
                        <option value="">{{ __('admin.products.select_game') }}</option>
                        @foreach ($games as $game)
                            <option value="{{ $game->id }}">{{ $game->name }}</option>
                        @endforeach
                    </select>
                    @error('game_id')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-200" for="product-rarity">{{ __('admin.products.rarity_label') }}</label>
                    <select
                        id="product-rarity"
                        class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400"
                        wire:model="rarity_id"
                    >
                        <option value="">{{ __('admin.products.select_rarity') }}</option>
                        @foreach ($rarities as $rarity)
                            <option value="{{ $rarity->id }}">{{ $rarity->name }}</option>
                        @endforeach
                    </select>
                    @error('rarity_id')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save,image"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ __('admin.shared.create') }}
                    </span>
                    <span wire:loading wire:target="save">{{ __('admin.shared.saving') }}</span>
                </button>

                <button
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-70"
                    type="button"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                    wire:target="save,image"
                >
                    {{ __('shared.actions.cancel') }}
                </button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-800 bg-zinc-900/70">
        <table class="min-w-full divide-y divide-zinc-800">
            <thead class="bg-zinc-950/70">
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-zinc-500">
                    <th class="px-6 py-4">{{ __('admin.tables.product') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.game') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.rarity') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.stock') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.price') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('admin.tables.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img
                                    class="h-16 w-16 rounded-lg border border-zinc-800 object-cover"
                                    src="{{ $product->url_img !== '' ? $product->url_img : $fallbackImage }}"
                                    alt="{{ $product->name }}"
                                    onerror="this.onerror=null;this.src='{{ $fallbackImage }}';"
                                >
                                <div class="min-w-0 max-w-[18rem]">
                                    <p class="truncate font-medium text-white" title="{{ $product->name }}">{{ $product->name }}</p>
                                    <p class="text-sm text-zinc-400">#{{ $product->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->game->name }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->rarity->name }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->quantity }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->price }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a class="rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" href="{{ route('admin.products.edit', ['product' => $product]) }}">
                                    {{ __('admin.shared.edit') }}
                                </a>

                                @if ($confirmingDeleteProductId === $product->id)
                                    <button class="rounded-lg bg-red-400 px-3 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-red-300 disabled:cursor-not-allowed disabled:opacity-70" type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                                        <span wire:loading.remove wire:target="delete">{{ __('admin.shared.confirm_delete') }}</span>
                                        <span wire:loading wire:target="delete">{{ __('admin.shared.deleting') }}</span>
                                    </button>
                                    <button class="rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" type="button" wire:click="$set('confirmingDeleteProductId', null)">
                                        {{ __('admin.shared.cancel_delete') }}
                                    </button>
                                @else
                                    <button class="rounded-lg border border-red-500/60 px-3 py-2 text-sm font-medium text-red-200 transition hover:border-red-400 hover:bg-red-950/40" type="button" wire:click="confirmDelete({{ $product->id }})">
                                        {{ __('admin.shared.delete') }}
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-zinc-400" colspan="6">{{ __('admin.products.empty_state') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $products->links() }}
    </div>
</section>
