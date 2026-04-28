@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 640 480\"><rect width=\"640\" height=\"480\" fill=\"#0f172a\"/><circle cx=\"509\" cy=\"112\" r=\"56\" fill=\"#14b8a6\" fill-opacity=\"0.35\"/><circle cx=\"112\" cy=\"392\" r=\"76\" fill=\"#f59e0b\" fill-opacity=\"0.24\"/><text x=\"50%\" y=\"48%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#e2e8f0\" font-family=\"sans-serif\" font-size=\"34\">GR-Shop</text><text x=\"50%\" y=\"58%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#94a3b8\" font-family=\"sans-serif\" font-size=\"18\">Catalog preview</text></svg>'))

<section class="mx-auto max-w-7xl px-4 py-12">
    <div class="rounded-[2rem] border border-white/10 bg-slate-900/60 p-6 shadow-[0_32px_90px_rgba(8,15,28,0.45)] md:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">{{ __('storefront.catalog.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-semibold text-white">{{ __('storefront.catalog.title') }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-300">{{ __('storefront.catalog.summary') }}</p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-950/70 px-5 py-4 text-left">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.catalog.products_found_label') }}</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ $totalProductCount }}</p>
                @if ($products !== null && $products->total() > $products->count())
                    <p class="mt-1 text-xs text-slate-400">
                        {{ __('storefront.catalog.page_count_label', ['from' => $products->firstItem(), 'to' => $products->lastItem()]) }}
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            @forelse ($games as $game)
                <a
                    class="{{ $selectedGameSlug === $game['slug'] ? 'border-teal-300 bg-teal-400/15 text-white' : 'border-white/10 bg-white/5 text-slate-300 hover:border-white/20 hover:text-white' }} inline-flex items-center gap-3 rounded-full border px-4 py-3 text-sm font-medium transition"
                    href="{{ route('storefront.catalog', ['game' => $game['slug']]) }}"
                >
                    <span>{{ $game['name'] }}</span>
                    <span class="rounded-full bg-slate-950/80 px-2.5 py-1 text-xs text-slate-400">{{ $game['count'] }}</span>
                </a>
            @empty
                <span class="rounded-full border border-dashed border-white/10 px-4 py-3 text-sm text-slate-500">
                    {{ __('storefront.catalog.no_games') }}
                </span>
            @endforelse
        </div>
    </div>

    <div class="mt-10">
        @if ($products !== null && $products->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($products as $product)
                    <x-storefront.product-card :product="$product" :fallback-image="$fallbackImage" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="rounded-[2rem] border border-dashed border-white/10 bg-slate-900/40 px-6 py-16 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">{{ __('shared.states.empty') }}</p>
                <h2 class="mt-4 text-2xl font-semibold text-white">{{ __('storefront.catalog.empty_title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-400">{{ __('storefront.catalog.empty_description') }}</p>
            </div>
        @endif
    </div>
</section>
