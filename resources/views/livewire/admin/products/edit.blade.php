@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240"><rect width="320" height="240" fill="#111827"/><circle cx="250" cy="58" r="34" fill="#34d399" fill-opacity="0.35"/><text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="#e5e7eb" font-family="sans-serif" font-size="18">GR-Shop</text></svg>'))

<section class="space-y-6">
    <div>
        <a class="text-sm font-medium text-emerald-300 transition hover:text-emerald-200" href="{{ route('admin.products.index') }}">
            {{ __('admin.shared.back_to_index') }}
        </a>
        <h1 class="mt-3 text-3xl font-semibold text-white">{{ __('admin.products.edit_title') }}</h1>
        <p class="mt-3 max-w-3xl truncate text-zinc-300" title="{{ $product->name }}">#{{ $product->id }} · {{ $product->name }}</p>
    </div>

    <form class="space-y-5 rounded-lg border border-zinc-800 bg-zinc-900/70 p-5" wire:submit="save">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-zinc-200" for="product-name">{{ __('admin.products.name_label') }}</label>
                <input id="product-name" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition placeholder:text-zinc-600 focus:border-emerald-400" type="text" wire:model="name" autocomplete="off">
                @error('name')
                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-200" for="product-image">{{ __('admin.products.image_label') }}</label>
                <input id="product-image" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-zinc-100 hover:file:bg-zinc-700" type="file" wire:model="image" accept="image/jpeg,image/png,image/webp">
                <p class="mt-2 text-sm text-zinc-400">{{ __('admin.products.image_help_update') }}</p>
                @error('image')
                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-200" for="product-quantity">{{ __('admin.products.quantity_label') }}</label>
                <input id="product-quantity" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400" type="number" min="0" wire:model="quantity">
                @error('quantity')
                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-200" for="product-price">{{ __('admin.products.price_label') }}</label>
                <input id="product-price" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400" type="number" min="0" wire:model="price">
                @error('price')
                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-200" for="product-game">{{ __('admin.products.game_label') }}</label>
                <select id="product-game" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400" wire:model="game_id">
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
                <select id="product-rarity" class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition focus:border-emerald-400" wire:model="rarity_id">
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

        <div class="flex items-center gap-4 rounded-lg border border-zinc-800 bg-zinc-950 p-4">
            <img class="h-16 w-16 rounded-lg border border-zinc-800 object-cover" src="{{ $currentImageUrl !== '' ? $currentImageUrl : $fallbackImage }}" alt="{{ __('admin.products.current_image') }}" onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
            <span class="text-sm text-zinc-300">{{ __('admin.products.current_image') }}</span>
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70" type="submit" wire:loading.attr="disabled" wire:target="save,image">
                <span wire:loading.remove wire:target="save">{{ __('admin.shared.update') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.shared.saving') }}</span>
            </button>
            <a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" href="{{ route('admin.products.index') }}">
                {{ __('shared.actions.cancel') }}
            </a>
        </div>
    </form>
</section>
