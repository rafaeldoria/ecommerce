<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable();
            $table->string('role')->default(User::ROLE_CUSTOMER);
        });

        DB::table('users')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => 'user-'.$user->id]);
            });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users ALTER COLUMN username SET NOT NULL');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'customer'))");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role']);
        });
    }
};
