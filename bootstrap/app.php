<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Modules\Admin\Exceptions\InvalidAdminCredentials;
use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $renderJsonError = static function (string $message, string $error, int $status): JsonResponse {
            return response()->json([
                'message' => $message,
                'error' => $error,
            ], $status);
        };

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError(__('general.api.errors.unauthenticated'), 'unauthenticated', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError(
                $exception->getMessage() !== '' ? $exception->getMessage() : __('general.api.errors.forbidden'),
                'forbidden',
                403,
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError(
                $exception->getMessage() !== '' ? $exception->getMessage() : __('general.api.errors.forbidden'),
                'forbidden',
                403,
            );
        });

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => __('general.api.errors.validation_failed'),
                'error' => 'validation_failed',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError(__('general.api.errors.resource_not_found'), 'resource_not_found', 404);
        });

        $exceptions->render(function (InvalidAdminCredentials $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError($exception->getMessage(), 'invalid_admin_credentials', 422);
        });

        $exceptions->render(function (CatalogResourceInUse $exception, Request $request) use ($renderJsonError): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return $renderJsonError($exception->getMessage(), 'catalog_resource_in_use', 422);
        });
    })->create();
