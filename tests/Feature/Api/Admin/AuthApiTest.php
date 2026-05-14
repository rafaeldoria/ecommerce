<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use App\Modules\Admin\Actions\ConfirmAdminMfaSetupAction;
use App\Modules\Admin\Actions\StartAdminMfaSetupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Fortify;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_log_in_with_username(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'secret-pass',
            'device_name' => 'postman',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.auth.logged_in'))
            ->assertJsonPath('data.user.id', $admin->getKey())
            ->assertJsonPath('data.user.role', User::ROLE_ADMIN)
            ->assertJson(fn ($json) => $json
                ->whereType('data.token', 'string')
                ->where('data.token', fn (string $token): bool => str_contains($token, '|grshop_'))
                ->etc()
            );

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $admin->getKey(),
            'name' => 'postman',
        ]);

        $this->assertNotNull($admin->tokens()->firstOrFail()->expires_at);
    }

    #[Test]
    public function admin_can_log_in_with_email(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
        ]);

        $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'device_name' => 'insomnia',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $admin->getKey())
            ->assertJsonPath('data.user.email', 'admin@example.com');
    }

    #[Test]
    public function mfa_enabled_admin_login_returns_challenge_without_creating_token(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->enableMfa($admin);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'secret-pass',
            'device_name' => 'postman',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.auth.mfa_required'))
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonPath('data.user.id', $admin->getKey())
            ->assertJson(fn ($json) => $json
                ->whereType('data.challenge_id', 'string')
                ->whereType('data.expires_at', 'string')
                ->missing('data.token')
                ->etc()
            );

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $admin->getKey(),
            'name' => 'postman',
        ]);
    }

    #[Test]
    public function valid_mfa_challenge_creates_admin_token(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->enableMfa($admin);
        $challengeId = $this->startApiMfaChallenge();
        $this->travel(31)->seconds();

        $this->postJson('/api/admin/auth/mfa-challenge', [
            'challenge_id' => $challengeId,
            'code' => $this->currentTotpCode($admin->refresh()),
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.auth.logged_in'))
            ->assertJson(fn ($json) => $json
                ->whereType('data.token', 'string')
                ->where('data.user.id', $admin->getKey())
                ->etc()
            );

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $admin->getKey(),
            'name' => 'postman',
        ]);
    }

    #[Test]
    public function mfa_recovery_code_is_single_use_for_api_login(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $recoveryCode = $this->enableMfa($admin)->firstRecoveryCode;
        $challengeId = $this->startApiMfaChallenge();

        $this->postJson('/api/admin/auth/mfa-challenge', [
            'challenge_id' => $challengeId,
            'recovery_code' => $recoveryCode,
        ])->assertOk();

        $secondChallengeId = $this->startApiMfaChallenge();

        $response = $this->postJson('/api/admin/auth/mfa-challenge', [
            'challenge_id' => $secondChallengeId,
            'recovery_code' => $recoveryCode,
        ]);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );
    }

    #[Test]
    public function expired_mfa_challenge_returns_problem_details(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->enableMfa($admin);
        $challengeId = $this->startApiMfaChallenge();

        Cache::flush();

        $response = $this->postJson('/api/admin/auth/mfa-challenge', [
            'challenge_id' => $challengeId,
            'code' => '123456',
        ]);

        $this->assertProblemDetails(
            $response,
            'mfa_challenge_expired',
            422,
            __('general.errors.mfa_challenge_expired'),
        );
    }

    #[Test]
    public function required_mfa_unconfirmed_admin_cannot_log_in_through_api(): void
    {
        config(['security.admin_mfa.required' => true]);

        User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'secret-pass',
            'device_name' => 'postman',
        ]);

        $this->assertProblemDetails(
            $response,
            'mfa_setup_required',
            409,
            __('general.errors.mfa_setup_required'),
        );
    }

    #[Test]
    public function login_rejects_invalid_admin_credentials(): void
    {
        User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'wrong-pass',
            'device_name' => 'postman',
        ]);

        $this->assertProblemDetails(
            $response,
            'invalid_admin_credentials',
            422,
            __('general.errors.invalid_admin_credentials'),
        );
    }

    #[Test]
    public function login_throttle_uses_a_normalized_identifier_bucket(): void
    {
        User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        foreach (['ops-admin', ' ops-admin', 'ops-admin ', ' OPS-ADMIN ', 'ops-admin'] as $username) {
            $this->postJson('/api/admin/auth/login', [
                'username' => $username,
                'password' => 'wrong-pass',
                'device_name' => 'postman',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/admin/auth/login', [
            'username' => '  ops-admin  ',
            'password' => 'wrong-pass',
            'device_name' => 'postman',
        ])->assertStatus(429);
    }

    #[Test]
    public function login_throttle_uses_email_when_username_is_empty(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
        ]);

        $payloads = [
            ['username' => null, 'email' => 'admin@example.com'],
            ['username' => '', 'email' => ' admin@example.com '],
            ['username' => '   ', 'email' => 'ADMIN@example.com'],
            ['username' => null, 'email' => 'Admin@example.com '],
            ['username' => '', 'email' => 'admin@example.com'],
        ];

        foreach ($payloads as $index => $payload) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.10.0.{$index}"])
                ->postJson('/api/admin/auth/login', [
                    ...$payload,
                    'password' => 'wrong-pass',
                    'device_name' => 'postman',
                ])->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.99'])
            ->postJson('/api/admin/auth/login', [
                'username' => ' ',
                'email' => ' admin@example.com ',
                'password' => 'wrong-pass',
                'device_name' => 'postman',
            ])->assertStatus(429);
    }

    #[Test]
    public function login_rejects_customers(): void
    {
        User::factory()->customer()->create([
            'username' => 'shopper',
            'password' => 'secret-pass',
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'shopper',
            'password' => 'secret-pass',
            'device_name' => 'browser',
        ]);

        $this->assertProblemDetails(
            $response,
            'invalid_admin_credentials',
            422,
            __('general.errors.invalid_admin_credentials'),
        );
    }

    #[Test]
    public function admin_can_fetch_their_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('dashboard');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.auth.profile_retrieved'))
            ->assertJsonPath('data.id', $admin->getKey())
            ->assertJsonPath('data.username', $admin->username);
    }

    #[Test]
    public function logout_revokes_only_the_current_token(): void
    {
        $admin = User::factory()->admin()->create();
        $firstToken = $admin->createToken('dashboard');
        $secondToken = $admin->createToken('mobile');

        $this->withToken($firstToken->plainTextToken)
            ->postJson('/api/admin/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.auth.logged_out'));

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $firstToken->accessToken->getKey(),
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $secondToken->accessToken->getKey(),
        ]);
    }

    #[Test]
    public function admin_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/me');

        $this->assertProblemDetails(
            $response,
            'unauthenticated',
            401,
            __('general.api.errors.unauthenticated'),
        );
    }

    #[Test]
    public function admin_profile_forbids_customers(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->withToken($customer->createToken('shopper')->plainTextToken)
            ->getJson('/api/admin/me');

        $this->assertProblemDetails(
            $response,
            'forbidden',
            403,
            __('general.api.errors.forbidden'),
        );
    }

    #[Test]
    public function login_requires_a_username_or_email_and_device_name(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'password' => 'secret-pass',
        ]);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['username', 'email', 'device_name']);
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

    private function startApiMfaChallenge(): string
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'secret-pass',
            'device_name' => 'postman',
        ])->assertOk();

        return (string) $response->json('data.challenge_id');
    }
}
