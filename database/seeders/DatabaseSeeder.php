<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'username' => 'test-user',
        ], [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => User::ROLE_CUSTOMER,
        ]);

        User::query()->updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
