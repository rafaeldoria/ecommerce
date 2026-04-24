@php($fallbackImage = "data:image/svg+xml,".rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 320 240\"><rect width=\"320\" height=\"240\" fill=\"#111827\"/><circle cx=\"250\" cy=\"58\" r=\"34\" fill=\"#34d399\" fill-opacity=\"0.35\"/><text x=\"50%\" y=\"52%\" dominant-baseline=\"middle\" text-anchor=\"middle\" fill=\"#e5e7eb\" font-family=\"sans-serif\" font-size=\"18\">GR-Shop</text></svg>'))

<section class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">{{ __('admin.products.title') }}</h1>
        <p class="mt-3 text-zinc-300">{{ __('admin.products.summary') }}</p>
    </div>

    <div class="overflow-hidden rounded-[2rem] border border-zinc-800 bg-zinc-900/70">
        <table class="min-w-full divide-y divide-zinc-800">
            <thead class="bg-zinc-950/70">
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-zinc-500">
                    <th class="px-6 py-4">{{ __('admin.tables.product') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.game') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.rarity') }}</th>
                    <th class="px-6 py-4">{{ __('admin.tables.stock') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img
                                    class="h-16 w-16 rounded-2xl border border-zinc-800 object-cover"
                                    src="{{ $product->url_img !== '' ? $product->url_img : $fallbackImage }}"
                                    alt="{{ $product->name }}"
                                    onerror="this.onerror=null;this.src='{{ $fallbackImage }}';"
                                >
                                <div>
                                    <p class="font-medium text-white">{{ $product->name }}</p>
                                    <p class="text-sm text-zinc-400">#{{ $product->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->game->name }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->rarity->name }}</td>
                        <td class="px-6 py-4 text-zinc-300">{{ $product->quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-zinc-400" colspan="4">{{ __('admin.shared.empty_state') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
