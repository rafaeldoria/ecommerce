@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 320 240\"><rect width=\"320\" height=\"240\" fill=\"#0f172a\"/><circle cx=\"250\" cy=\"58\" r=\"34\" fill=\"#14b8a6\" fill-opacity=\"0.35\"/><text x=\"50%\" y=\"52%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#e2e8f0\" font-family=\"sans-serif\" font-size=\"18\">GR-Shop</text></svg>'))

<section class="mx-auto max-w-6xl px-4 py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-normal text-white">{{ __('storefront.cart.title') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-300">{{ __('storefront.cart.foundation_note') }}</p>
        </div>

        @if ($items !== [])
            <a class="inline-flex items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.checkout') }}">
                {{ __('storefront.cart.checkout') }}
            </a>
        @endif
    </div>

    @if (session('cart.status'))
        <div class="mt-6 rounded-2xl border border-teal-400/40 bg-teal-400/10 px-4 py-3 text-sm font-semibold text-teal-100" role="status">
            {{ session('cart.status') }}
        </div>
    @endif

    @if ($items === [])
        <div class="mt-8 rounded-[2rem] border border-dashed border-white/10 bg-slate-900/40 px-6 py-16 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">{{ __('shared.states.empty') }}</p>
            <h2 class="mt-4 text-2xl font-semibold text-white">{{ __('storefront.cart.empty_title') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-400">{{ __('storefront.cart.empty_description') }}</p>
            <a class="mt-6 inline-flex items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.catalog') }}">
                {{ __('storefront.cart.continue_shopping') }}
            </a>
        </div>
    @else
        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-4">
                @foreach ($items as $item)
                    <article class="rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-4">
                        <div class="grid gap-4 sm:grid-cols-[6rem_1fr_auto] sm:items-center">
                            <img
                                class="h-24 w-24 rounded-2xl border border-white/10 object-cover"
                                src="{{ $item['image_url'] !== '' ? $item['image_url'] : $fallbackImage }}"
                                alt="{{ $item['product_name'] }}"
                                onerror="this.onerror=null;this.src='{{ $fallbackImage }}';"
                            >

                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-white" title="{{ $item['product_name'] }}">{{ $item['product_name'] }}</h2>
                                <p class="mt-2 text-sm text-slate-400">{{ __('storefront.cart.unit_price', ['price' => $item['formatted_unit_price']]) }}</p>
                                <p class="mt-1 text-sm font-medium text-slate-200">{{ __('storefront.cart.subtotal', ['price' => $item['formatted_subtotal']]) }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                <label class="sr-only" for="cart-quantity-{{ $item['product_id'] }}">{{ __('storefront.cart.quantity_label') }}</label>
                                <input
                                    id="cart-quantity-{{ $item['product_id'] }}"
                                    class="h-11 w-20 rounded-lg border border-white/10 bg-slate-950 px-3 text-white outline-none transition focus:border-teal-300"
                                    type="number"
                                    min="1"
                                    wire:model="quantities.{{ $item['product_id'] }}"
                                    wire:change="updateQuantity({{ $item['product_id'] }})"
                                >
                                @error("quantities.{$item['product_id']}")
                                    <p class="basis-full text-sm text-red-300">{{ $message }}</p>
                                @enderror
                                <button
                                    class="inline-flex h-11 items-center justify-center rounded-lg border border-red-400/50 px-3 text-sm font-semibold text-red-200 transition hover:bg-red-950/40 disabled:cursor-not-allowed disabled:opacity-70"
                                    type="button"
                                    wire:click="removeItem({{ $item['product_id'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="removeItem({{ $item['product_id'] }})"
                                >
                                    {{ __('shared.actions.remove') }}
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="h-fit rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-5">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.cart.total_label') }}</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ $total }}</p>
                <p class="mt-4 text-sm leading-6 text-slate-400">{{ __('storefront.cart.checkout_note') }}</p>
                <a class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.checkout') }}">
                    {{ __('storefront.cart.checkout') }}
                </a>
            </aside>
        </div>
    @endif
</section>
