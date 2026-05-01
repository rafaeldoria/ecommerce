<x-layouts.storefront :title="__('storefront.payment_return.metadata_title')">
    <section class="mx-auto max-w-4xl px-4 py-16">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">{{ __('storefront.payment_return.eyebrow') }}</p>
        <h1 class="mt-4 text-3xl font-semibold tracking-normal text-white">
            {{ __("storefront.payment_return.{$status}.title") }}
        </h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
            {{ __("storefront.payment_return.{$status}.description") }}
        </p>

        <dl class="mt-8 grid gap-3 rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">{{ __('storefront.payment_return.status_label') }}</dt>
                <dd class="mt-1 font-semibold text-white">{{ request('status', $status) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('storefront.payment_return.payment_id_label') }}</dt>
                <dd class="mt-1 font-semibold text-white">{{ request('payment_id', '-') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('storefront.payment_return.preference_id_label') }}</dt>
                <dd class="mt-1 font-semibold text-white">{{ request('preference_id', '-') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('storefront.payment_return.external_reference_label') }}</dt>
                <dd class="mt-1 font-semibold text-white">{{ request('external_reference', '-') }}</dd>
            </div>
        </dl>

        <a class="mt-8 inline-flex items-center justify-center rounded-full bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300" href="{{ route('storefront.cart') }}">
            {{ __('storefront.payment_return.back_to_cart') }}
        </a>
    </section>
</x-layouts.storefront>
