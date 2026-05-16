<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ChangePassword;
use App\Livewire\Admin\Login;
use App\Models\User;
use App\Modules\Admin\Actions\ConfirmAdminMfaSetupAction;
use App\Modules\Admin\Actions\StartAdminMfaSetupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function dashboard_links_to_change_password_page(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->enableMfa($admin);

        $this->actingAs($admin->refresh())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard.change_password'))
            ->assertSee(route('admin.password.edit'), false)
            ->assertSee('admin@example.com');
    }

    #[Test]
    public function admin_with_confirmed_mfa_can_access_change_password_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->enableMfa($admin);

        $this->actingAs($admin->refresh())
            ->get(route('admin.password.edit'))
            ->assertOk()
            ->assertSee('<title>Change password</title>', false)
            ->assertSee(__('admin.password.new_password_help'));
    }

    #[Test]
    public function change_password_page_requires_confirmed_mfa(): void
    {
        config(['security.admin_mfa.required' => false]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.password.edit'))
            ->assertForbidden();
    }

    #[Test]
    public function current_password_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'old-pass1!',
        ]);

        $this->enableMfa($admin);
        $this->travel(31)->seconds();

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'wrong-pass')
            ->set('newPassword', 'New-pass1!')
            ->set('newPasswordConfirmation', 'New-pass1!')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('old-pass1!', $admin->refresh()->password));
    }

    #[Test]
    public function new_password_must_match_complexity_rules(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'old-pass1!',
        ]);

        $this->enableMfa($admin);
        $this->travel(31)->seconds();

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'abcdef!')
            ->set('newPasswordConfirmation', 'abcdef!')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasErrors(['newPassword']);

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'abcdef1!')
            ->set('newPasswordConfirmation', 'abcdef1!')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasErrors(['newPassword']);

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'Abcdef1')
            ->set('newPasswordConfirmation', 'Abcdef1')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasErrors(['newPassword']);
    }

    #[Test]
    public function new_password_confirmation_must_match(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'old-pass1!',
        ]);

        $this->enableMfa($admin);

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'New-pass1!')
            ->set('newPasswordConfirmation', 'Different1!')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasErrors(['newPasswordConfirmation']);
    }

    #[Test]
    public function mfa_code_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'old-pass1!',
        ]);

        $this->enableMfa($admin);

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'New-pass1!')
            ->set('newPasswordConfirmation', 'New-pass1!')
            ->set('mfaCode', '000000')
            ->call('changePassword')
            ->assertHasErrors(['code']);

        $this->assertTrue(Hash::check('old-pass1!', $admin->refresh()->password));
    }

    #[Test]
    public function valid_change_updates_password_and_new_password_can_start_login(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'old-pass1!',
        ]);

        $this->enableMfa($admin);
        $this->travel(31)->seconds();

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'New-pass1!')
            ->set('newPasswordConfirmation', 'New-pass1!')
            ->set('mfaCode', $this->currentTotpCode($admin->refresh()))
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertSet('currentPassword', '')
            ->assertSet('newPassword', '')
            ->assertSet('newPasswordConfirmation', '')
            ->assertSet('mfaCode', '')
            ->assertSee(__('admin.password.messages.changed'));

        $this->assertTrue(Hash::check('New-pass1!', $admin->refresh()->password));

        auth()->logout();

        Livewire::test(Login::class)
            ->set('login', 'ops-admin')
            ->set('password', 'New-pass1!')
            ->call('authenticate')
            ->assertSet('showMfaChallenge', true);
    }

    #[Test]
    public function recovery_code_is_not_accepted_as_password_change_mfa_code(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'old-pass1!',
        ]);

        $recoveryCode = $this->enableMfa($admin);

        Livewire::actingAs($admin->refresh())
            ->test(ChangePassword::class)
            ->set('currentPassword', 'old-pass1!')
            ->set('newPassword', 'New-pass1!')
            ->set('newPasswordConfirmation', 'New-pass1!')
            ->set('mfaCode', $recoveryCode)
            ->call('changePassword')
            ->assertHasErrors(['code']);

        $this->assertTrue(Hash::check('old-pass1!', $admin->refresh()->password));
    }

    private function enableMfa(User $admin): string
    {
        $result = app(StartAdminMfaSetupAction::class)->execute($admin);
        $code = $this->currentTotpCode($admin->refresh());

        app(ConfirmAdminMfaSetupAction::class)->execute($admin, $code);
        Cache::flush();

        return $result->recoveryCodes[0];
    }

    private function currentTotpCode(User $admin): string
    {
        $secret = Fortify::currentEncrypter()->decrypt((string) $admin->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
