<section class="space-y-6">
    <div>
        <a class="text-sm font-medium text-emerald-300 transition hover:text-emerald-200" href="{{ route('admin.rarities.index') }}">
            {{ __('admin.shared.back_to_index') }}
        </a>
        <h1 class="mt-3 text-3xl font-semibold text-white">{{ __('admin.rarities.edit_title') }}</h1>
        <p class="mt-3 text-zinc-300">#{{ $rarity->id }} · {{ $rarity->name }}</p>
    </div>

    <form class="space-y-5 rounded-lg border border-zinc-800 bg-zinc-900/70 p-5" wire:submit="save">
        <div>
            <label class="text-sm font-medium text-zinc-200" for="rarity-name">{{ __('admin.rarities.name_label') }}</label>
            <input
                id="rarity-name"
                class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition placeholder:text-zinc-600 focus:border-emerald-400"
                type="text"
                wire:model="name"
                autocomplete="off"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70" type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('admin.shared.update') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.shared.saving') }}</span>
            </button>
            <a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" href="{{ route('admin.rarities.index') }}">
                {{ __('shared.actions.cancel') }}
            </a>
        </div>
    </form>
</section>
