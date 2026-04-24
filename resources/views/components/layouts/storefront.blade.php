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
        <div class="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top,_rgba(45,212,191,0.15),_transparent_30%),linear-gradient(180deg,_rgba(15,23,42,0.98),_rgba(2,6,23,1))]">
            <header class="sticky top-0 z-20 border-b border-white/10 bg-slate-950/90 backdrop-blur">
                <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4" aria-label="{{ __('storefront.navigation.primary') }}">
                    <a class="flex items-center gap-3" href="{{ route('storefront.home') }}">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-400/15 text-sm font-semibold tracking-[0.24em] text-teal-200">
                            GR
                        </span>
                        <span>
                            <span class="block text-lg font-semibold tracking-[0.18em] text-white uppercase">{{ __('storefront.brand.name') }}</span>
                            <span class="block text-xs text-slate-400">{{ __('storefront.brand.tagline') }}</span>
                        </span>
                    </a>

                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-300">
                        <a class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white" href="{{ route('storefront.home') }}">
                            {{ __('storefront.navigation.home') }}
                        </a>
                        <a class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white" href="{{ route('storefront.catalog') }}">
                            {{ __('storefront.navigation.catalog') }}
                        </a>
                        <a class="inline-flex items-center rounded-full border border-teal-400/40 bg-teal-400/10 px-4 py-2 font-medium text-teal-100 transition hover:border-teal-300 hover:bg-teal-400/20" href="{{ route('storefront.cart') }}">
                            {{ __('storefront.navigation.cart') }}
                        </a>
                    </div>
                </nav>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-white/10 bg-slate-950/85 px-4 py-14 text-sm text-slate-300">
                <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
                    <section class="space-y-5">
                        <div>
                            <h2 class="text-xl font-semibold text-teal-300">{{ __('storefront.footer.about_title') }}</h2>
                            <p class="mt-4 max-w-md text-base leading-7 text-slate-400">
                                {{ __('storefront.footer.about_text') }}
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-teal-300">{{ __('storefront.footer.social_title') }}</h3>
                            <div class="mt-4 flex items-center gap-3">
                                @foreach (__('storefront.footer.social_links') as $social)
                                    <span class="inline-flex rounded-full border border-white/10 px-3 py-2 text-sm text-slate-300">
                                        {{ $social }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-teal-300">{{ __('storefront.footer.quick_links_title') }}</h2>
                        <ul class="mt-4 space-y-3 text-base text-slate-400">
                            <li><a class="transition hover:text-white" href="{{ route('storefront.home') }}">{{ __('storefront.navigation.home') }}</a></li>
                            <li><a class="transition hover:text-white" href="{{ route('storefront.catalog') }}">{{ __('storefront.navigation.catalog') }}</a></li>
                            <li><a class="transition hover:text-white" href="{{ route('storefront.about') }}">{{ __('storefront.navigation.about') }}</a></li>
                            <li><a class="transition hover:text-white" href="{{ route('storefront.contact') }}">{{ __('storefront.navigation.contact') }}</a></li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-teal-300">{{ __('storefront.footer.support_title') }}</h2>
                        <ul class="mt-4 space-y-3 text-base text-slate-400">
                            <li><a class="transition hover:text-white" href="{{ route('storefront.faq') }}">{{ __('storefront.navigation.faq') }}</a></li>
                            <li>{{ __('storefront.footer.privacy') }}</li>
                            <li>{{ __('storefront.footer.terms') }}</li>
                            <li>{{ __('storefront.footer.support_note') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-teal-300">{{ __('storefront.footer.contact_title') }}</h2>
                        <ul class="mt-4 space-y-3 text-base text-slate-400">
                            <li>{{ __('storefront.footer.phone') }}</li>
                            <li>{{ __('storefront.footer.whatsapp') }}</li>
                            <li>{{ __('storefront.footer.email') }}</li>
                            <li>{{ __('storefront.footer.location') }}</li>
                        </ul>
                    </section>
                </div>

                <div class="mx-auto mt-10 max-w-7xl border-t border-white/10 pt-6 text-center text-sm text-slate-500">
                    <p>{{ __('storefront.footer.copyright') }}</p>
                </div>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
