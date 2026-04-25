<section class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_0.95fr]">
    <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-300">{{ __('admin.auth.eyebrow') }}</p>
        <h1 class="mt-4 text-4xl font-semibold text-white">{{ __('admin.auth.login_title') }}</h1>
        <p class="mt-4 max-w-xl text-base leading-7 text-zinc-300">{{ __('admin.auth.summary') }}</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-3xl border border-zinc-800 bg-zinc-950/70 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.auth.panel_title') }}</p>
                <p class="mt-2 text-sm text-zinc-300">{{ __('admin.auth.panel_text') }}</p>
            </div>
            <div class="rounded-3xl border border-zinc-800 bg-zinc-950/70 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">{{ __('admin.auth.security_title') }}</p>
                <p class="mt-2 text-sm text-zinc-300">{{ __('admin.auth.security_text') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/80 p-8 shadow-[0_24px_70px_rgba(0,0,0,0.35)]">
        <form class="space-y-5" wire:submit="authenticate">
            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="login">
                    {{ __('admin.auth.login_label') }}
                </label>
                <input
                    id="login"
                    type="text"
                    wire:model.defer="login"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    placeholder="{{ __('admin.auth.login_placeholder') }}"
                    autocomplete="username"
                >
                @error('login')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="password">
                    {{ __('admin.auth.password_label') }}
                </label>
                <input
                    id="password"
                    type="password"
                    wire:model.defer="password"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    placeholder="{{ __('admin.auth.password_placeholder') }}"
                    autocomplete="current-password"
                >
                @error('password')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <button class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300" type="submit">
                {{ __('admin.auth.submit') }}
            </button>
        </form>
    </div>
</section>
