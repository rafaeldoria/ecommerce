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
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-zinc-800 bg-zinc-950">
                <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4" aria-label="{{ __('admin.navigation.primary') }}">
                    <a class="text-base font-semibold tracking-[0.18em] text-white uppercase" href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}">
                        {{ __('admin.brand.name') }}
                    </a>

                    @auth
                        <div class="flex flex-wrap items-center gap-4 text-sm text-zinc-300">
                            <a class="transition hover:text-white" href="{{ route('admin.dashboard') }}">{{ __('admin.navigation.dashboard') }}</a>
                            <a class="transition hover:text-white" href="{{ route('admin.games.index') }}">{{ __('admin.navigation.games') }}</a>
                            <a class="transition hover:text-white" href="{{ route('admin.rarities.index') }}">{{ __('admin.navigation.rarities') }}</a>
                            <a class="transition hover:text-white" href="{{ route('admin.products.index') }}">{{ __('admin.navigation.products') }}</a>
                            <a class="transition hover:text-white" href="{{ route('admin.orders.index') }}">{{ __('admin.navigation.orders') }}</a>

                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button class="rounded-full border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-900" type="submit">
                                    {{ __('admin.navigation.logout') }}
                                </button>
                            </form>
                        </div>
                    @endauth
                </nav>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
