<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsAdmin(?User $user = null): User
    {
        $user ??= User::factory()->admin()->create();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsCustomer(?User $user = null): User
    {
        $user ??= User::factory()->customer()->create();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function assertProblemDetails(TestResponse $response, string $code, int $status, string $detail): void
    {
        $response
            ->assertStatus($status)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', '/problems/'.str_replace('_', '-', $code))
            ->assertJsonPath('status', $status)
            ->assertJsonPath('detail', $detail)
            ->assertJsonPath('code', strtoupper($code));
    }
}
