@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 640 480\"><rect width=\"640\" height=\"480\" fill=\"#0f172a\"/><circle cx=\"509\" cy=\"112\" r=\"56\" fill=\"#14b8a6\" fill-opacity=\"0.35\"/><circle cx=\"112\" cy=\"392\" r=\"76\" fill=\"#f59e0b\" fill-opacity=\"0.24\"/><text x=\"50%\" y=\"48%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#e2e8f0\" font-family=\"sans-serif\" font-size=\"34\">GR-Shop</text><text x=\"50%\" y=\"58%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#94a3b8\" font-family=\"sans-serif\" font-size=\"18\">Featured item</text></svg>'))

<section class="mx-auto max-w-7xl px-4 py-16">
    <div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
        <div class="rounded-[2.4rem] border border-white/10 bg-slate-900/65 p-8 shadow-[0_32px_90px_rgba(8,15,28,0.45)] md:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.26em] text-teal-300">{{ __('storefront.home.eyebrow') }}</p>
            <h1 class="mt-5 max-w-4xl text-5xl font-semibold leading-tight text-white">{{ __('storefront.home.title') }}</h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300">{{ __('storefront.home.summary') }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a class="inline-flex items-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.catalog') }}">
                    {{ __('storefront.home.primary_cta') }}
                </a>
                <a class="inline-flex items-center rounded-full border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/20 hover:bg-white/5" href="{{ route('storefront.contact') }}">
                    {{ __('storefront.home.secondary_cta') }}
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <article class="rounded-[2rem] border border-white/10 bg-slate-900/60 p-6">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">{{ __('storefront.home.metric_games_label') }}</p>
                <p class="mt-3 text-4xl font-semibold text-white">{{ count($games) }}</p>
                <p class="mt-3 text-sm leading-7 text-slate-400">{{ __('storefront.home.metric_games_text') }}</p>
            </article>
            <article class="rounded-[2rem] border border-white/10 bg-slate-900/60 p-6">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">{{ __('storefront.home.metric_inventory_label') }}</p>
                <p class="mt-3 text-4xl font-semibold text-white">{{ collect($games)->sum('count') }}</p>
                <p class="mt-3 text-sm leading-7 text-slate-400">{{ __('storefront.home.metric_inventory_text') }}</p>
            </article>
        </div>
    </div>

    <div class="mt-12 rounded-[2rem] border border-white/10 bg-slate-900/50 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-300">{{ __('storefront.home.games_eyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">{{ __('storefront.home.games_title') }}</h2>
            </div>
            <a class="text-sm font-medium text-teal-300 transition hover:text-teal-200" href="{{ route('storefront.catalog') }}">
                {{ __('storefront.home.games_link') }}
            </a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ($games as $game)
                <a class="rounded-[1.6rem] border border-white/10 bg-slate-950/65 p-5 transition hover:border-teal-400/40 hover:bg-slate-950" href="{{ route('storefront.catalog', ['game' => $game['slug']]) }}">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-white">{{ $game['name'] }}</h3>
                            <p class="mt-2 text-sm text-slate-400">{{ __('storefront.home.games_card_text', ['count' => $game['count']]) }}</p>
                        </div>
                        <span class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-300">{{ $game['count'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-12">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">{{ __('storefront.home.featured_eyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">{{ __('storefront.home.featured_title') }}</h2>
            </div>
            <a class="text-sm font-medium text-teal-300 transition hover:text-teal-200" href="{{ route('storefront.catalog') }}">
                {{ __('storefront.home.featured_link') }}
            </a>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($featuredProducts as $product)
                <x-storefront.product-card :product="$product" :fallback-image="$fallbackImage" />
            @empty
                <div class="col-span-full rounded-[2rem] border border-dashed border-white/10 bg-slate-900/40 px-6 py-12 text-center text-slate-400">
                    {{ __('storefront.home.no_featured_products') }}
                </div>
            @endforelse
        </div>
    </div>
</section>
