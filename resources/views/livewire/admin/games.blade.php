<section class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">{{ __('admin.games.title') }}</h1>
        <p class="mt-3 text-zinc-300">{{ __('admin.games.summary') }}</p>
    </div>

    <div class="overflow-hidden rounded-[2rem] border border-zinc-800 bg-zinc-900/70">
        <table class="min-w-full divide-y divide-zinc-800">
            <thead class="bg-zinc-950/70">
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-zinc-500">
                    <th class="px-6 py-4">{{ __('admin.tables.name') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.id') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($games as $game)
                    <tr>
                        <td class="px-6 py-4 text-white">{{ $game->name }}</td>
                        <td class="px-6 py-4 text-zinc-400">#{{ $game->id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-zinc-400" colspan="2">{{ __('admin.shared.empty_state') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
