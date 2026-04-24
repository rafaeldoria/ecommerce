<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title ?? __('admin.metadata.default_title') }}</title>
        @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-zinc-800 bg-zinc-950">
                <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4" aria-label="{{ __('admin.navigation.primary') }}">
                    <a class="text-base font-semibold tracking-normal text-white" href="{{ route('admin.dashboard') }}">
                        {{ __('admin.brand.name') }}
                    </a>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-zinc-300">
                        <a href="{{ route('admin.dashboard') }}">{{ __('admin.navigation.dashboard') }}</a>
                        <a href="{{ route('admin.games.index') }}">{{ __('admin.navigation.games') }}</a>
                        <a href="{{ route('admin.rarities.index') }}">{{ __('admin.navigation.rarities') }}</a>
                        <a href="{{ route('admin.products.index') }}">{{ __('admin.navigation.products') }}</a>
                        <a href="{{ route('admin.orders.index') }}">{{ __('admin.navigation.orders') }}</a>
                    </div>
                </nav>
            </header>

            <main class="mx-auto max-w-6xl px-4 py-8">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
