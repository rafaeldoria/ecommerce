<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">{{ __('admin.rarities.title') }}</h1>
            <p class="mt-3 text-zinc-300">{{ __('admin.rarities.summary') }}</p>
        </div>

        <button
            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
            type="button"
            wire:click="beginCreate"
            wire:loading.attr="disabled"
            wire:target="beginCreate"
        >
            {{ __('admin.rarities.create') }}
        </button>
    </div>

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
                {{ $editingRarityId === null ? __('admin.rarities.create_title') : __('admin.rarities.edit_title') }}
            </h2>

            <div>
                <label class="text-sm font-medium text-zinc-200" for="rarity-name">{{ __('admin.rarities.name_label') }}</label>
                <input
                    id="rarity-name"
                    class="mt-2 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white outline-none transition placeholder:text-zinc-600 focus:border-emerald-400"
                    type="text"
                    wire:model="name"
                    placeholder="{{ __('admin.rarities.name_placeholder') }}"
                    autocomplete="off"
                >
                @error('name')
                    <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $editingRarityId === null ? __('admin.shared.create') : __('admin.shared.update') }}
                    </span>
                    <span wire:loading wire:target="save">{{ __('admin.shared.saving') }}</span>
                </button>

                <button
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-70"
                    type="button"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                    wire:target="save"
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
                    <th class="px-6 py-4">{{ __('admin.tables.name') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.id') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('admin.tables.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($rarities as $rarity)
                    <tr>
                        <td class="px-6 py-4 text-white">{{ $rarity->name }}</td>
                        <td class="px-6 py-4 text-zinc-400">#{{ $rarity->id }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button class="rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" type="button" wire:click="edit({{ $rarity->id }})">
                                    {{ __('admin.shared.edit') }}
                                </button>

                                @if ($confirmingDeleteRarityId === $rarity->id)
                                    <button class="rounded-lg bg-red-400 px-3 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-red-300 disabled:cursor-not-allowed disabled:opacity-70" type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                                        <span wire:loading.remove wire:target="delete">{{ __('admin.shared.confirm_delete') }}</span>
                                        <span wire:loading wire:target="delete">{{ __('admin.shared.deleting') }}</span>
                                    </button>
                                    <button class="rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-800" type="button" wire:click="$set('confirmingDeleteRarityId', null)">
                                        {{ __('admin.shared.cancel_delete') }}
                                    </button>
                                @else
                                    <button class="rounded-lg border border-red-500/60 px-3 py-2 text-sm font-medium text-red-200 transition hover:border-red-400 hover:bg-red-950/40" type="button" wire:click="confirmDelete({{ $rarity->id }})">
                                        {{ __('admin.shared.delete') }}
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-zinc-400" colspan="3">{{ __('admin.rarities.empty_state') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
