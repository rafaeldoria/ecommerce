<section class="mx-auto grid max-w-5xl gap-6">
    <div class="rounded-3xl border border-zinc-800 bg-zinc-900/80 p-6">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-300">{{ __('admin.security.eyebrow') }}</p>
        <h1 class="mt-3 text-3xl font-semibold text-white">{{ __('admin.security.title') }}</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-300">{{ __('admin.security.summary') }}</p>

        @if ($statusMessage !== null)
            <p class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ $statusMessage }}
            </p>
        @endif

        @if ($mfaRequired && !$admin->hasConfirmedMfa())
            <p class="mt-5 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                {{ __('admin.security.required_notice') }}
            </p>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
        <div class="rounded-3xl border border-zinc-800 bg-zinc-900/80 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-white">{{ __('admin.security.totp_title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-300">{{ __('admin.security.totp_summary') }}</p>
                </div>

                @if ($admin->hasConfirmedMfa())
                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                        {{ __('admin.security.enabled_badge') }}
                    </span>
                @else
                    <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-100">
                        {{ __('admin.security.disabled_badge') }}
                    </span>
                @endif
            </div>

            @if (!$admin->hasConfirmedMfa() && !$setupInProgress)
                <button
                    class="mt-6 inline-flex items-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                    type="button"
                    wire:click="startSetup"
                    wire:loading.attr="disabled"
                >
                    {{ __('admin.security.start_setup') }}
                </button>
            @endif

            @if ($setupInProgress)
                <div class="mt-6 grid gap-5">
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-950 p-4">
                        <div class="inline-block rounded-xl bg-white p-3 text-zinc-950">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>

                    <form class="space-y-4" wire:submit="confirmSetup">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-200" for="confirmationCode">
                                {{ __('admin.security.confirmation_code_label') }}
                            </label>
                            <input
                                id="confirmationCode"
                                type="text"
                                inputmode="numeric"
                                wire:model.defer="confirmationCode"
                                class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-emerald-400"
                                autocomplete="one-time-code"
                            >
                            @error('confirmationCode')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                            @error('code')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="inline-flex items-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300" type="submit">
                            {{ __('admin.security.confirm_setup') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="rounded-3xl border border-zinc-800 bg-zinc-900/80 p-6">
            <h2 class="text-xl font-semibold text-white">{{ __('admin.security.recovery_codes_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-300">{{ __('admin.security.recovery_codes_summary') }}</p>

            @if ($recoveryCodes !== [])
                <p class="mt-5 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                    {{ __('admin.security.recovery_codes_copy_notice') }}
                </p>

                <div class="mt-5 grid gap-2">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <code class="rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100">{{ $recoveryCode }}</code>
                    @endforeach
                </div>

                @if ($admin->hasConfirmedMfa())
                    <button
                        class="mt-5 inline-flex items-center rounded-2xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-950"
                        type="button"
                        wire:click="regenerateRecoveryCodes"
                    >
                        {{ __('admin.security.regenerate_recovery_codes') }}
                    </button>
                @endif
            @elseif ($admin->hasConfirmedMfa())
                <p class="mt-5 rounded-2xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-zinc-400">
                    {{ __('admin.security.recovery_codes_hidden') }}
                </p>

                <button
                    class="mt-5 inline-flex items-center rounded-2xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-100 transition hover:border-zinc-500 hover:bg-zinc-950"
                    type="button"
                    wire:click="regenerateRecoveryCodes"
                >
                    {{ __('admin.security.regenerate_recovery_codes') }}
                </button>
            @else
                <p class="mt-5 rounded-2xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-zinc-400">
                    {{ __('admin.security.recovery_codes_empty') }}
                </p>
            @endif
        </div>
    </div>

    @if ($admin->hasConfirmedMfa())
        <div class="rounded-3xl border border-rose-500/20 bg-rose-950/20 p-6">
            <h2 class="text-xl font-semibold text-white">{{ __('admin.security.disable_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-300">{{ __('admin.security.disable_summary') }}</p>

            <form class="mt-5 grid gap-4 sm:grid-cols-[1fr_auto]" wire:submit="disableMfa">
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-200" for="disablePassword">
                        {{ __('admin.security.disable_password_label') }}
                    </label>
                    <input
                        id="disablePassword"
                        type="password"
                        wire:model.defer="disablePassword"
                        class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-100 outline-none transition focus:border-rose-400"
                        autocomplete="current-password"
                    >
                    @error('disablePassword')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <button class="self-end rounded-2xl border border-rose-400/50 px-5 py-3 text-sm font-semibold text-rose-100 transition hover:bg-rose-500/10" type="submit">
                    {{ __('admin.security.disable_submit') }}
                </button>
            </form>
        </div>
    @endif
</section>
