<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Login;
use App\Livewire\Admin\Security;
use App\Models\User;
use App\Modules\Admin\Actions\ConfirmAdminMfaSetupAction;
use App\Modules\Admin\Actions\DisableAdminMfaAction;
use App\Modules\Admin\Actions\StartAdminMfaSetupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function setup_generates_encrypted_secret_and_recovery_codes(): void
    {
        $admin = User::factory()->admin()->create();

        $result = app(StartAdminMfaSetupAction::class)->execute($admin);

        $admin->refresh();

        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNotNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertCount(8, $result->recoveryCodes);
        $this->assertStringContainsString('<svg', $result->qrCodeSvg);
        $this->assertStringStartsWith('otpauth://totp/', $result->qrCodeUrl);
    }

    #[Test]
    public function valid_totp_confirms_mfa_and_invalid_totp_fails(): void
    {
        $admin = User::factory()->admin()->create();

        app(StartAdminMfaSetupAction::class)->execute($admin);

        try {
            app(ConfirmAdminMfaSetupAction::class)->execute($admin->refresh(), '000000');
            $this->fail('Invalid TOTP code was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        }

        $code = $this->currentTotpCode($admin->refresh());

        app(ConfirmAdminMfaSetupAction::class)->execute($admin, $code);

        $this->assertNotNull($admin->refresh()->two_factor_confirmed_at);
        $this->assertTrue($admin->hasConfirmedMfa());
    }

    #[Test]
    public function recovery_code_works_once_for_web_login(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $recoveryCode = $this->enableMfa($admin)->firstRecoveryCode;

        Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->assertSet('showMfaChallenge', true)
            ->set('recoveryCode', $recoveryCode)
            ->call('verifyMfa')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        auth()->logout();

        Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->set('recoveryCode', $recoveryCode)
            ->call('verifyMfa')
            ->assertHasErrors(['recovery_code']);
    }

    #[Test]
    public function mfa_enabled_admin_can_log_in_with_totp_after_password(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->enableMfa($admin);
        $this->travel(31)->seconds();

        $component = Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->assertSet('showMfaChallenge', true)
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('verifyMfa');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function required_mfa_forces_setup_before_dashboard(): void
    {
        config(['security.admin_mfa.required' => true]);

        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'secret-pass')
            ->call('authenticate')
            ->assertRedirect(route('admin.security'));

        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.security'));
    }

    #[Test]
    public function required_mfa_still_blocks_dashboard_after_setup_starts_until_confirmation(): void
    {
        config(['security.admin_mfa.required' => true]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.security'))
            ->assertOk();

        $this->assertNotNull($admin->refresh()->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.security'));
    }

    #[Test]
    public function admin_menu_only_shows_security_until_mfa_is_confirmed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.security'))
            ->assertOk()
            ->assertSee('href="'.route('admin.security').'"', false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('href="'.route('admin.games.index').'"', false)
            ->assertDontSee('href="'.route('admin.products.index').'"', false)
            ->assertDontSee('href="'.route('admin.orders.index').'"', false);
    }

    #[Test]
    public function admin_menu_hides_security_after_mfa_is_confirmed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->enableMfa($admin);

        $this->actingAs($admin->refresh())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertSee('href="'.route('admin.products.index').'"', false)
            ->assertDontSee('href="'.route('admin.security').'"', false);
    }

    #[Test]
    public function recovery_codes_are_only_visible_immediately_after_generation(): void
    {
        config(['security.admin_mfa.required' => false]);

        $admin = User::factory()->admin()->create();

        $component = Livewire::actingAs($admin)
            ->test(Security::class)
            ->call('startSetup')
            ->assertSet('recoveryCodes', [])
            ->assertDontSee('otpauth://totp')
            ->assertDontSee(__('admin.security.recovery_codes_copy_notice'));

        $admin->refresh();
        $confirmationCode = $this->currentTotpCode($admin);

        $component
            ->set('confirmationCode', $confirmationCode)
            ->call('confirmSetup')
            ->assertHasNoErrors()
            ->assertSee(__('admin.security.recovery_codes_copy_notice'));

        $firstRecoveryCode = $admin->refresh()->recoveryCodes()[0];

        $this->actingAs($admin->refresh())
            ->get(route('admin.security'))
            ->assertOk()
            ->assertSee(__('admin.security.recovery_codes_hidden'))
            ->assertDontSee($firstRecoveryCode);

        Livewire::test(Security::class)
            ->call('regenerateRecoveryCodes')
            ->assertSee(__('admin.security.recovery_codes_copy_notice'));
    }

    #[Test]
    public function disable_mfa_requires_current_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->enableMfa($admin);

        $this->actingAs($admin);

        Livewire::test(Security::class)
            ->set('disablePassword', 'wrong-password')
            ->call('disableMfa')
            ->assertHasErrors(['disablePassword']);

        app(DisableAdminMfaAction::class)->execute($admin->refresh(), 'password');

        $this->assertFalse($admin->refresh()->hasConfirmedMfa());
    }

    private function enableMfa(User $admin): object
    {
        $result = app(StartAdminMfaSetupAction::class)->execute($admin);
        $code = $this->currentTotpCode($admin->refresh());

        app(ConfirmAdminMfaSetupAction::class)->execute($admin, $code);
        Cache::flush();

        return (object) [
            'firstRecoveryCode' => $result->recoveryCodes[0],
        ];
    }

    private function currentTotpCode(User $admin): string
    {
        $secret = Fortify::currentEncrypter()->decrypt((string) $admin->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
