<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\DTOs\AdminMfaApiChallenge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CreateAdminMfaApiChallengeAction
{
    public function execute(User $admin, string $deviceName): AdminMfaApiChallenge
    {
        $challengeId = (string) Str::uuid();
        $ttl = $this->ttlSeconds();
        $expiresAt = now()->addSeconds($ttl);

        Cache::put($this->cacheKey($challengeId), [
            'user_id' => $admin->getKey(),
            'device_name' => $deviceName,
            'expires_at' => $expiresAt->getTimestamp(),
            'attempts' => 0,
        ], $expiresAt);

        return new AdminMfaApiChallenge($challengeId, $expiresAt);
    }

    public function cacheKey(string $challengeId): string
    {
        return 'admin:mfa:api-challenge:'.$challengeId;
    }

    private function ttlSeconds(): int
    {
        $ttl = (int) config('security.admin_mfa.challenge_ttl_seconds', 300);

        return $ttl > 0 ? $ttl : 300;
    }
}
