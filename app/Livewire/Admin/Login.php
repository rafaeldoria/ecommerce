<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Admin\Actions\AuthenticateAdminAction;
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

    public function mount(): void
    {
        if (Auth::guard('web')->check() && Auth::user()?->isAdmin()) {
            $this->redirectRoute('admin.dashboard', navigate: false);
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

        Auth::guard('web')->login($user, true);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.login');
    }

    protected function titleKey(): string
    {
        return 'admin.auth.login_title';
    }

    private function rateLimitKey(string $login): string
    {
        $normalized = Str::lower(trim($login));

        if ($normalized === '') {
            return (string) request()->ip();
        }

        return $normalized;
    }
}
