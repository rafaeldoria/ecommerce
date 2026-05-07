<section class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4 rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-6">
        <div>
            <h1 class="text-3xl font-semibold text-white">{{ __('admin.orders.detail_title') }} #{{ $foundOrder->id }}</h1>
            <p class="mt-3 text-zinc-300">{{ __('admin.orders.detail_summary') }}</p>
        </div>

        <div class="rounded-3xl border border-zinc-800 bg-zinc-950/70 px-5 py-4 text-right">
            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.total_label') }}</p>
            <p class="mt-2 text-2xl font-semibold text-white">{{ $formattedTotalAmount }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
        <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-6">
            <h2 class="text-xl font-semibold text-white">{{ __('admin.orders.contact_block_title') }}</h2>
            <dl class="mt-5 space-y-4">
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.tables.contact') }}</dt>
                    <dd class="mt-1 text-zinc-200">{{ $foundOrder->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.whatsapp_label') }}</dt>
                    <dd class="mt-1 text-zinc-200">{{ $foundOrder->whatsapp }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.tables.status') }}</dt>
                    <dd class="mt-1 text-zinc-200">{{ $foundOrder->status }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-white">{{ __('admin.orders.payment_block_title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">
                        {{ __('admin.orders.payment_block_summary') }}
                    </p>
                </div>

                <span @class([
                    'rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-emerald-400/10 text-emerald-300' => $manualFulfillmentAllowed,
                    'bg-amber-400/10 text-amber-300' => ! $manualFulfillmentAllowed,
                ])>
                    {{ $manualFulfillmentAllowed
                        ? __('admin.orders.fulfillment_allowed')
                        : __('admin.orders.fulfillment_blocked') }}
                </span>
            </div>

            @if ($latestPayment)
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.payment_status_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">{{ $latestPayment->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.provider_payment_id_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">{{ $latestPayment->provider_payment_id ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.provider_status_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">{{ $latestPayment->provider_status ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.provider_status_detail_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">{{ $latestPayment->provider_status_detail ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.last_provider_update_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">{{ $formattedPaymentUpdatedAt }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.orders.webhook_journal_label') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-100">
                            @if ($latestWebhook)
                                #{{ $latestWebhook->id }} - {{ $latestWebhook->processing_status }} - {{ $formattedWebhookReceivedAt }}
                            @else
                                {{ __('admin.orders.no_webhook_journal') }}
                            @endif
                        </dd>
                    </div>
                </dl>
            @else
                <p class="mt-5 rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm leading-6 text-zinc-400">
                    {{ __('admin.orders.no_payment_recorded') }}
                </p>
            @endif
        </div>

        <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-6">
            <h2 class="text-xl font-semibold text-white">{{ __('admin.orders.items_block_title') }}</h2>
            <div class="mt-5 space-y-4">
                @foreach ($foundOrder->items as $item)
                    <article class="rounded-3xl border border-zinc-800 bg-zinc-950/70 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium text-white">{{ $item->product_name }}</p>
                                <p class="mt-1 text-sm text-zinc-500">#{{ $item->product_id }}</p>
                            </div>

                            <div class="text-right text-sm text-zinc-300">
                                <p>{{ __('admin.orders.quantity_label', ['count' => $item->quantity]) }}</p>
                                <p class="mt-1">{{ \App\Support\MoneyFormatter::brlFromCents($item->unit_price) }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
