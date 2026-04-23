<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
}
