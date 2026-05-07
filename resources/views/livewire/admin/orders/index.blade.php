<section class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">{{ __('admin.orders.title') }}</h1>
        <p class="mt-3 text-zinc-300">{{ __('admin.orders.summary') }}</p>
    </div>

    <div class="overflow-hidden rounded-[2rem] border border-zinc-800 bg-zinc-900/70">
        <table class="min-w-full divide-y divide-zinc-800">
            <thead class="bg-zinc-950/70">
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-zinc-500">
                    <th class="px-6 py-4">{{ __('admin.tables.order') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.contact') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.status') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.items') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('admin.tables.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($orders as $order)
                    @php($orderDetailUrl = route('admin.orders.show', ['order' => $order->id]))
                    <tr>
                        <td class="px-6 py-4">
                            <a class="font-medium text-white transition hover:text-emerald-300" href="{{ $orderDetailUrl }}">
                                #{{ $order->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-zinc-300">
                            <div>{{ $order->email }}</div>
                            <div class="text-sm text-zinc-500">{{ $order->whatsapp }}</div>
                        </td>
                        <td class="px-6 py-4 text-zinc-300">{{ $order->status }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $order->items->sum('quantity') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-emerald-400/70 hover:bg-zinc-800" href="{{ $orderDetailUrl }}">
                                {{ __('admin.orders.open_detail') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-zinc-400" colspan="5">{{ __('admin.shared.empty_state') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $orders->links() }}
    </div>
</section>
