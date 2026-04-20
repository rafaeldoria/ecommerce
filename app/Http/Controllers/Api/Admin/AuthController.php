<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AdminLoginRequest;
use App\Models\User;
use App\Modules\Admin\Actions\AuthenticateAdminAction;
use App\Modules\Admin\DTOs\AdminLoginData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthenticateAdminAction $authenticateAdminAction,
    ) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $validated = $request->validated();

            $user = $this->authenticateAdminAction->execute(new AdminLoginData(
                username: isset($validated['username']) ? (string) $validated['username'] : null,
                email: isset($validated['email']) ? (string) $validated['email'] : null,
                password: (string) $validated['password'],
                deviceName: (string) $validated['device_name'],
            ));

            return response()->json([
                'message' => __('general.api.admin.auth.logged_in'),
                'data' => [
                    'token' => $user->createToken((string) $validated['device_name'])->plainTextToken,
                    'user' => $this->userPayload($user),
                ],
            ]);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $request->user()?->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('general.api.admin.auth.logged_out'),
            ]);
        });
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond(fn () => response()->json([
            'message' => __('general.api.admin.auth.profile_retrieved'),
            'data' => $this->userPayload($request->user()),
        ]));
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
}
