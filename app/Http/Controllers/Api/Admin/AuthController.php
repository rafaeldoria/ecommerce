<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AdminLoginRequest;
use App\Http\Requests\Api\Admin\AdminMfaChallengeRequest;
use App\Models\User;
use App\Modules\Admin\Actions\AuthenticateAdminAction;
use App\Modules\Admin\Actions\CompleteAdminMfaApiChallengeAction;
use App\Modules\Admin\Actions\CreateAdminMfaApiChallengeAction;
use App\Modules\Admin\DTOs\AdminLoginData;
use App\Modules\Admin\Exceptions\AdminMfaSetupRequired;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthenticateAdminAction $authenticateAdminAction,
        private readonly CreateAdminMfaApiChallengeAction $createAdminMfaApiChallengeAction,
        private readonly CompleteAdminMfaApiChallengeAction $completeAdminMfaApiChallengeAction,
    ) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->authenticateAdminAction->execute(new AdminLoginData(
            username: isset($validated['username']) ? (string) $validated['username'] : null,
            email: isset($validated['email']) ? (string) $validated['email'] : null,
            password: (string) $validated['password'],
            deviceName: (string) $validated['device_name'],
        ));

        if ($this->requiresMfaSetup($user)) {
            throw new AdminMfaSetupRequired(__('general.errors.mfa_setup_required'));
        }

        if ($user->hasConfirmedMfa()) {
            $challenge = $this->createAdminMfaApiChallengeAction->execute($user, (string) $validated['device_name']);

            return response()->json([
                'message' => __('general.api.admin.auth.mfa_required'),
                'data' => [
                    'mfa_required' => true,
                    'challenge_id' => $challenge->id,
                    'expires_at' => $challenge->expiresAt->format(DATE_ATOM),
                    'user' => $this->userPayload($user),
                ],
            ]);
        }

        return response()->json([
            'message' => __('general.api.admin.auth.logged_in'),
            'data' => [
                'token' => $this->issueToken($user, (string) $validated['device_name']),
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function mfaChallenge(AdminMfaChallengeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $challenge = $this->completeAdminMfaApiChallengeAction->execute(
            (string) $validated['challenge_id'],
            isset($validated['code']) ? (string) $validated['code'] : null,
            isset($validated['recovery_code']) ? (string) $validated['recovery_code'] : null,
        );

        return response()->json([
            'message' => __('general.api.admin.auth.logged_in'),
            'data' => [
                'token' => $this->issueToken($challenge->admin, $challenge->deviceName),
                'user' => $this->userPayload($challenge->admin),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => __('general.api.admin.auth.logged_out'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('general.api.admin.auth.profile_retrieved'),
            'data' => $this->userPayload($request->user()),
        ]);
    }

    private function userPayload(?User $user): array
    {
        return [
            'id' => $user?->getKey(),
            'name' => $user?->name,
            'username' => $user?->username,
            'email' => $user?->email,
            'role' => $user?->role,
        ];
    }

    private function tokenExpiration(): ?\DateTimeInterface
    {
        $minutes = config('sanctum.expiration');

        if ($minutes === null) {
            return null;
        }

        $minutes = (int) $minutes;

        return $minutes > 0 ? now()->addMinutes($minutes) : null;
    }

    private function issueToken(User $user, string $deviceName): string
    {
        return $user->createToken(
            $deviceName,
            ['*'],
            $this->tokenExpiration(),
        )->plainTextToken;
    }

    private function requiresMfaSetup(User $user): bool
    {
        return (bool) config('security.admin_mfa.required', false) && !$user->hasConfirmedMfa();
    }
}
