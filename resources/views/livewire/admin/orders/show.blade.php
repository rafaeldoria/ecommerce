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
