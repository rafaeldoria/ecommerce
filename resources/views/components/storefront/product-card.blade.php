@props([
    'product',
    'fallbackImage',
])

<article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 shadow-[0_24px_80px_rgba(8,15,28,0.45)] transition duration-300 hover:-translate-y-1 hover:border-teal-400/40 hover:shadow-[0_28px_90px_rgba(13,148,136,0.18)]">
    <a class="relative block overflow-hidden bg-slate-950" href="{{ $product['route'] }}">
        <img
            class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
            src="{{ $product['image_url'] !== '' ? $product['image_url'] : $fallbackImage }}"
            alt="{{ $product['name'] }}"
            loading="lazy"
            onerror="this.onerror=null;this.src='{{ $fallbackImage }}';"
        >

        <div class="absolute inset-x-0 top-0 flex items-center justify-between gap-3 p-4">
            <span class="rounded-full border border-white/10 bg-slate-950/75 px-3 py-1 text-xs font-semibold tracking-[0.18em] text-slate-200 uppercase">
                {{ $product['game'] }}
            </span>
            <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-200">
                {{ $product['rarity'] }}
            </span>
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-4 p-5">
        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-white">{{ $product['name'] }}</h3>
            <p class="text-sm text-slate-400">
                {{ __('storefront.catalog.stock_label', ['count' => $product['quantity']]) }}
            </p>
        </div>

        <div class="mt-auto flex items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ __('storefront.catalog.price_label') }}</p>
                <p class="mt-1 text-2xl font-semibold text-white">{{ $product['formatted_price'] }}</p>
            </div>

            <a class="inline-flex items-center rounded-full bg-teal-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ $product['route'] }}">
                {{ __('storefront.catalog.view_product') }}
            </a>
        </div>
    </div>
</article>
