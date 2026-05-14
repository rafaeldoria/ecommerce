<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Models\User;
use App\Modules\Admin\Actions\AuthenticateAdminAction;
use App\Modules\Admin\Actions\VerifyAdminMfaChallengeAction;
use App\Modules\Admin\DTOs\AdminLoginData;
use App\Modules\Admin\Exceptions\InvalidAdminCredentials;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Login extends Component
{
    use UsesLocalizedPageTitle;

    public string $login = '';

    public string $password = '';

    public string $mfaCode = '';

    public string $recoveryCode = '';

    public bool $showMfaChallenge = false;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isAdmin()) {
            $this->redirect($this->postLoginRoute($user), navigate: false);
        }
    }

    public function authenticate(AuthenticateAdminAction $authenticateAdminAction)
    {
        $validated = $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $rateLimitKey = $this->rateLimitKey($validated['login']);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'login' => __('admin.auth.throttled'),
            ]);
        }

        try {
            $identifier = trim($validated['login']);
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

            $user = $authenticateAdminAction->execute(new AdminLoginData(
                username: $isEmail ? null : $identifier,
                email: $isEmail ? $identifier : null,
                password: $validated['password'],
                deviceName: 'admin-web',
            ));
        } catch (InvalidAdminCredentials) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'login' => __('general.errors.invalid_admin_credentials'),
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        if ($user->hasConfirmedMfa()) {
            $this->startMfaChallenge($user);

            return null;
        }

        Auth::guard('web')->login($user, true);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended($this->postLoginRoute($user));
    }

    public function verifyMfa(VerifyAdminMfaChallengeAction $verifyAdminMfaChallengeAction)
    {
        $validated = $this->validate([
            'mfaCode' => ['nullable', 'string', 'required_without:recoveryCode'],
            'recoveryCode' => ['nullable', 'string', 'required_without:mfaCode'],
        ]);

        $user = $this->pendingMfaUser();

        if (!$user instanceof User) {
            $this->clearMfaChallenge();

            throw ValidationException::withMessages([
                'mfaCode' => __('admin.security.errors.challenge_expired'),
            ]);
        }

        if ($this->tooManyMfaAttempts()) {
            $this->clearMfaChallenge();

            throw ValidationException::withMessages([
                'mfaCode' => __('admin.security.errors.too_many_attempts'),
            ]);
        }

        try {
            $verifyAdminMfaChallengeAction->execute(
                $user,
                isset($validated['mfaCode']) ? trim((string) $validated['mfaCode']) : null,
                isset($validated['recoveryCode']) ? trim((string) $validated['recoveryCode']) : null,
            );
        } catch (ValidationException $exception) {
            $this->recordMfaAttempt();

            throw $exception;
        }

        $this->clearMfaChallenge();

        Auth::guard('web')->login($user, true);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function cancelMfaChallenge(): void
    {
        $this->clearMfaChallenge();

        $this->showMfaChallenge = false;
    }

    public function render()
    {
        return $this->pageView('livewire.admin.login');
    }

    protected function titleKey(): string
    {
        return 'admin.auth.login_title';
    }

    private function rateLimitKey(string $login): string
    {
        $normalized = Str::lower(trim($login));
        $ipAddress = (string) request()->ip();

        if ($normalized === '') {
            return $ipAddress;
        }

        return sprintf('%s|%s', $normalized, $ipAddress);
    }

    private function startMfaChallenge(User $user): void
    {
        $this->showMfaChallenge = true;
        $this->password = '';
        $this->mfaCode = '';
        $this->recoveryCode = '';

        session()->put('admin_mfa_login', [
            'user_id' => $user->getKey(),
            'expires_at' => now()->addSeconds($this->challengeTtlSeconds())->getTimestamp(),
            'attempts' => 0,
        ]);
    }

    private function pendingMfaUser(): ?User
    {
        $payload = session()->get('admin_mfa_login');

        if (!is_array($payload)) {
            return null;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        $user = User::query()->find((int) ($payload['user_id'] ?? 0));

        return $user instanceof User && $user->isAdmin() ? $user : null;
    }

    private function recordMfaAttempt(): void
    {
        $payload = session()->get('admin_mfa_login', []);

        if (!is_array($payload)) {
            return;
        }

        $payload['attempts'] = (int) ($payload['attempts'] ?? 0) + 1;

        session()->put('admin_mfa_login', $payload);
    }

    private function tooManyMfaAttempts(): bool
    {
        $payload = session()->get('admin_mfa_login', []);

        if (!is_array($payload)) {
            return true;
        }

        return (int) ($payload['attempts'] ?? 0) >= $this->maxAttempts();
    }

    private function clearMfaChallenge(): void
    {
        session()->forget('admin_mfa_login');

        $this->mfaCode = '';
        $this->recoveryCode = '';
    }

    private function postLoginRoute(User $user): string
    {
        if (!$user->hasConfirmedMfa()) {
            return route('admin.security');
        }

        return route('admin.dashboard');
    }

    private function maxAttempts(): int
    {
        $maxAttempts = (int) config('security.admin_mfa.max_attempts', 5);

        return $maxAttempts > 0 ? $maxAttempts : 5;
    }

    private function challengeTtlSeconds(): int
    {
        $ttl = (int) config('security.admin_mfa.challenge_ttl_seconds', 300);

        return $ttl > 0 ? $ttl : 300;
    }
}
