@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 640 480\"><rect width=\"640\" height=\"480\" fill=\"#0f172a\"/><circle cx=\"509\" cy=\"112\" r=\"56\" fill=\"#14b8a6\" fill-opacity=\"0.35\"/><circle cx=\"112\" cy=\"392\" r=\"76\" fill=\"#f59e0b\" fill-opacity=\"0.24\"/><text x=\"50%\" y=\"48%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#e2e8f0\" font-family=\"sans-serif\" font-size=\"34\">GR-Shop</text><text x=\"50%\" y=\"58%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#94a3b8\" font-family=\"sans-serif\" font-size=\"18\">Product image</text></svg>'))

<section class="mx-auto max-w-7xl px-4 py-12">
    <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/70">
            <img
                class="aspect-[4/3] w-full object-cover"
                src="{{ $product->url_img !== '' ? $product->url_img : $fallbackImage }}"
                alt="{{ $product->name }}"
                onerror="this.onerror=null;this.src='{{ $fallbackImage }}';"
            >
        </div>

        <div class="rounded-[2rem] border border-white/10 bg-slate-900/70 p-6 shadow-[0_24px_80px_rgba(8,15,28,0.45)] md:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">{{ $product->game->name }}</p>
            <h1 class="mt-4 text-4xl font-semibold text-white">{{ $product->name }}</h1>
            <p class="mt-4 inline-flex rounded-full border border-amber-400/30 bg-amber-400/10 px-4 py-2 text-sm font-medium text-amber-200">
                {{ $product->rarity->name }}
            </p>

            <div class="mt-8 space-y-4">
                <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.product.price_label') }}</p>
                    <p class="mt-2 text-4xl font-semibold text-white">{{ $formattedPrice }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.product.stock_label') }}</p>
                        <p class="mt-2 text-lg font-medium text-white">{{ __('storefront.product.stock_value', ['count' => $product->quantity]) }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.product.fulfillment_label') }}</p>
                        <p class="mt-2 text-lg font-medium text-white">{{ __('storefront.product.fulfillment_value') }}</p>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-base leading-7 text-slate-300">{{ __('storefront.product.summary') }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a class="inline-flex items-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.cart') }}">
                    {{ __('storefront.product.go_to_cart') }}
                </a>
                <a class="inline-flex items-center rounded-full border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/20 hover:bg-white/5" href="{{ route('storefront.catalog', ['game' => \Illuminate\Support\Str::slug($product->game->name)]) }}">
                    {{ __('storefront.product.back_to_catalog') }}
                </a>
            </div>
        </div>
    </div>
</section>
