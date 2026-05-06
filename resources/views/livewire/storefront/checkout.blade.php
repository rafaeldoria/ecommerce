<section class="mx-auto max-w-6xl px-4 py-12">
    <div>
        <h1 class="text-3xl font-semibold tracking-normal text-white">{{ __('storefront.checkout.title') }}</h1>
        <p class="mt-3 max-w-2xl text-slate-300">{{ __('storefront.checkout.foundation_note') }}</p>
    </div>

    @if ($items === [])
        <div class="mt-8 rounded-[1.5rem] border border-dashed border-white/10 bg-slate-900/50 px-6 py-12 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">{{ __('shared.states.empty') }}</p>
            <h2 class="mt-4 text-2xl font-semibold text-white">{{ __('storefront.cart.empty_title') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-400">{{ __('storefront.checkout.empty_description') }}</p>
            <a class="mt-6 inline-flex items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.catalog') }}">
                {{ __('storefront.cart.continue_shopping') }}
            </a>
        </div>
    @else
        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_24rem]">
            <form class="rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-5" wire:submit="pay">
                <h2 class="text-xl font-semibold text-white">{{ __('storefront.checkout.contact_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('storefront.checkout.contact_note') }}</p>

                <div class="mt-6 grid gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-200" for="checkout-email">{{ __('storefront.checkout.email_label') }}</label>
                        <input
                            id="checkout-email"
                            class="mt-2 h-12 w-full rounded-lg border border-white/10 bg-slate-950 px-3 text-white outline-none transition placeholder:text-slate-600 focus:border-teal-300"
                            type="email"
                            autocomplete="email"
                            wire:model="email"
                            placeholder="buyer@example.com"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-200" for="checkout-whatsapp">{{ __('storefront.checkout.whatsapp_label') }}</label>
                        <input
                            id="checkout-whatsapp"
                            class="mt-2 h-12 w-full rounded-lg border border-white/10 bg-slate-950 px-3 text-white outline-none transition placeholder:text-slate-600 focus:border-teal-300"
                            type="tel"
                            autocomplete="tel"
                            wire:model="whatsapp"
                            placeholder="+55 11 99999-1111"
                        >
                        @error('whatsapp')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @error('checkout')
                    <div class="mt-5 rounded-xl border border-red-400/40 bg-red-950/30 px-4 py-3 text-sm text-red-200" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                <button
                    class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300 disabled:cursor-not-allowed disabled:opacity-70"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="pay"
                >
                    <span wire:loading.remove wire:target="pay">{{ __('storefront.checkout.create_preference') }}</span>
                    <span wire:loading wire:target="pay">{{ __('storefront.checkout.redirecting') }}</span>
                </button>
            </form>

            <aside class="h-fit rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-5">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('storefront.cart.total_label') }}</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ $total }}</p>

                <div class="mt-6 space-y-4">
                    @foreach ($items as $item)
                        <div class="border-t border-white/10 pt-4 first:border-t-0 first:pt-0">
                            <div class="flex items-start justify-between gap-4">
                                <h2 class="text-sm font-semibold text-white">{{ $item['product_name'] }}</h2>
                                <span class="shrink-0 text-sm text-slate-300">x{{ $item['quantity'] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-400">{{ __('storefront.cart.unit_price', ['price' => $item['formatted_unit_price']]) }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-200">{{ __('storefront.cart.subtotal', ['price' => $item['formatted_subtotal']]) }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    @endif

</section>
