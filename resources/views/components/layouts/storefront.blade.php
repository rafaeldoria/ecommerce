<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('storefront.metadata.default.description') }}">
        <title>{{ $title ?? __('storefront.metadata.default.title') }}</title>
        @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-slate-800 bg-slate-950/95">
                <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4" aria-label="{{ __('storefront.navigation.primary') }}">
                    <a class="text-lg font-semibold tracking-normal text-white" href="{{ route('storefront.home') }}">
                        {{ __('storefront.brand.name') }}
                    </a>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-300">
                        <a href="{{ route('storefront.catalog') }}">{{ __('storefront.navigation.catalog') }}</a>
                        <a href="{{ route('storefront.about') }}">{{ __('storefront.navigation.about') }}</a>
                        <a href="{{ route('storefront.contact') }}">{{ __('storefront.navigation.contact') }}</a>
                        <a href="{{ route('storefront.faq') }}">{{ __('storefront.navigation.faq') }}</a>
                        <a class="rounded border border-teal-400/50 px-3 py-2 text-teal-200" href="{{ route('storefront.cart') }}">
                            {{ __('storefront.navigation.cart') }}
                        </a>
                    </div>
                </nav>
            </header>

            <main>
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-800 px-4 py-8 text-sm text-slate-400">
                <div class="mx-auto flex max-w-6xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>{{ __('storefront.footer.support_note') }}</p>
                    <p>{{ __('storefront.footer.locale_note') }}</p>
                </div>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
