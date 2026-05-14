<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\DTOs\VerifiedAdminMfaApiChallenge;
use App\Modules\Admin\Exceptions\AdminMfaChallengeExpired;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CompleteAdminMfaApiChallengeAction
{
    public function __construct(
        private readonly CreateAdminMfaApiChallengeAction $createAdminMfaApiChallengeAction,
        private readonly VerifyAdminMfaChallengeAction $verifyAdminMfaChallengeAction,
    ) {}

    public function execute(string $challengeId, ?string $code, ?string $recoveryCode): VerifiedAdminMfaApiChallenge
    {
        $cacheKey = $this->createAdminMfaApiChallengeAction->cacheKey($challengeId);
        $payload = Cache::get($cacheKey);

        if (!is_array($payload)) {
            throw new AdminMfaChallengeExpired(__('general.errors.mfa_challenge_expired'));
        }

        $this->guardChallengeAttempts($payload, $cacheKey);

        $admin = User::query()->find((int) ($payload['user_id'] ?? 0));

        if (!$admin instanceof User || !$admin->isAdmin()) {
            Cache::forget($cacheKey);

            throw new AdminMfaChallengeExpired(__('general.errors.mfa_challenge_expired'));
        }

        try {
            $this->verifyAdminMfaChallengeAction->execute($admin, $code, $recoveryCode);
        } catch (ValidationException $exception) {
            $this->recordFailedChallengeAttempt($payload, $cacheKey);

            throw $exception;
        }

        Cache::forget($cacheKey);

        return new VerifiedAdminMfaApiChallenge($admin, (string) $payload['device_name']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function guardChallengeAttempts(array $payload, string $cacheKey): void
    {
        $attempts = (int) ($payload['attempts'] ?? 0);

        if ($attempts < $this->maxAttempts()) {
            return;
        }

        Cache::forget($cacheKey);

        throw ValidationException::withMessages([
            'challenge_id' => __('admin.security.errors.too_many_attempts'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordFailedChallengeAttempt(array $payload, string $cacheKey): void
    {
        $payload['attempts'] = (int) ($payload['attempts'] ?? 0) + 1;
        $expiresAt = Carbon::createFromTimestamp((int) ($payload['expires_at'] ?? 0));

        if ($expiresAt->isPast()) {
            Cache::forget($cacheKey);

            return;
        }

        Cache::put($cacheKey, $payload, $expiresAt);
    }

    private function maxAttempts(): int
    {
        $maxAttempts = (int) config('security.admin_mfa.max_attempts', 5);

        return $maxAttempts > 0 ? $maxAttempts : 5;
    }
}
