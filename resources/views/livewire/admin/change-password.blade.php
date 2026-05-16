<section class="mx-auto grid max-w-3xl gap-6">
    <div class="rounded-[2rem] border border-zinc-800 bg-zinc-900/70 p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-300">{{ __('admin.password.eyebrow') }}</p>
        <h1 class="mt-3 text-3xl font-semibold text-white">{{ __('admin.password.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-300">{{ __('admin.password.summary') }}</p>

        @if ($statusMessage !== null)
            <p class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ $statusMessage }}
            </p>
        @endif
    </div>

    <form class="rounded-[2rem] border border-zinc-800 bg-zinc-900/80 p-8 shadow-[0_24px_70px_rgba(0,0,0,0.35)]" wire:submit="changePassword">
        <div class="grid gap-5">
            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="currentPassword">
                    {{ __('admin.password.current_password_label') }}
                </label>
                <input
                    id="currentPassword"
                    type="password"
                    wire:model.defer="currentPassword"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    autocomplete="current-password"
                >
                @error('currentPassword')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="newPassword">
                    {{ __('admin.password.new_password_label') }}
                </label>
                <input
                    id="newPassword"
                    type="password"
                    wire:model.defer="newPassword"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    autocomplete="new-password"
                >
                <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('admin.password.new_password_help') }}</p>
                @error('newPassword')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="newPasswordConfirmation">
                    {{ __('admin.password.new_password_confirmation_label') }}
                </label>
                <input
                    id="newPasswordConfirmation"
                    type="password"
                    wire:model.defer="newPasswordConfirmation"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    autocomplete="new-password"
                >
                @error('newPasswordConfirmation')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-200" for="mfaCode">
                    {{ __('admin.password.mfa_code_label') }}
                </label>
                <input
                    id="mfaCode"
                    type="text"
                    inputmode="numeric"
                    wire:model.defer="mfaCode"
                    class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                    autocomplete="one-time-code"
                >
                <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('admin.password.mfa_code_help') }}</p>
                @error('mfaCode')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
                @error('code')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300" type="submit">
                {{ __('admin.password.submit') }}
            </button>
            <a class="inline-flex w-full items-center justify-center rounded-2xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-950" href="{{ route('admin.dashboard') }}">
                {{ __('admin.password.cancel') }}
            </a>
        </div>
    </form>
</section>
