<section class="space-y-8">
    <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-8">
        <h1 class="text-3xl font-semibold text-white">{{ __('admin.dashboard.title') }}</h1>
        <p class="mt-3 max-w-3xl text-base leading-7 text-zinc-300">{{ __('admin.dashboard.summary') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a class="rounded-3xl border border-zinc-800 bg-zinc-900/70 p-5 transition hover:border-emerald-400/50 hover:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-emerald-400" href="{{ route('admin.games.index') }}">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.navigation.games') }}</p>
            <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['games'] }}</p>
        </a>
        <a class="rounded-3xl border border-zinc-800 bg-zinc-900/70 p-5 transition hover:border-emerald-400/50 hover:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-emerald-400" href="{{ route('admin.rarities.index') }}">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.navigation.rarities') }}</p>
            <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['rarities'] }}</p>
        </a>
        <a class="rounded-3xl border border-zinc-800 bg-zinc-900/70 p-5 transition hover:border-emerald-400/50 hover:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-emerald-400" href="{{ route('admin.products.index') }}">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.navigation.products') }}</p>
            <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['products'] }}</p>
        </a>
        <a class="rounded-3xl border border-zinc-800 bg-zinc-900/70 p-5 transition hover:border-emerald-400/50 hover:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-emerald-400" href="{{ route('admin.orders.index') }}">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.navigation.orders') }}</p>
            <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['orders'] }}</p>
        </a>
    </div>
</section>
