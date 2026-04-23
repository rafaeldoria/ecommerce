<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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
            ->assertJsonPath('data.user.role', User::ROLE_ADMIN);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $admin->getKey(),
            'name' => 'postman',
        ]);
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
    public function login_rejects_invalid_admin_credentials(): void
    {
        User::factory()->admin()->create([
            'username' => 'ops-admin',
            'password' => 'secret-pass',
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'ops-admin',
            'password' => 'wrong-pass',
            'device_name' => 'postman',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'invalid_admin_credentials')
            ->assertJsonPath('message', __('general.errors.invalid_admin_credentials'));
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

        $this->postJson('/api/admin/auth/login', [
            'username' => 'shopper',
            'password' => 'secret-pass',
            'device_name' => 'browser',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'invalid_admin_credentials');
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
        $this->getJson('/api/admin/me')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'unauthenticated')
            ->assertJsonPath('message', __('general.api.errors.unauthenticated'));
    }

    #[Test]
    public function admin_profile_forbids_customers(): void
    {
        $customer = User::factory()->customer()->create();

        $this->withToken($customer->createToken('shopper')->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('message', __('general.api.errors.forbidden'));
    }

    #[Test]
    public function login_requires_a_username_or_email_and_device_name(): void
    {
        $this->postJson('/api/admin/auth/login', [
            'password' => 'secret-pass',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['username', 'email', 'device_name']);
    }
}
