<?php

use App\Http\Middleware\ApplySessionLocale;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Responses\ApiProblemDetails;
use App\Modules\Admin\Exceptions\InvalidAdminCredentials;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference as CartInvalidProductReference;
use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference as CatalogInvalidProductReference;
use App\Modules\Catalog\Exceptions\ProductImageStorageFailed;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Exceptions\InvalidOrderContact;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $middleware->web(append: [
            ApplySessionLocale::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/mercado-pago',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('admin*')
            ? route('admin.login')
            : null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'unauthenticated', __('general.api.errors.unauthenticated'), 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make(
                $request,
                'forbidden',
                $exception->getMessage() !== '' ? $exception->getMessage() : __('general.api.errors.forbidden'),
                403,
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make(
                $request,
                'forbidden',
                $exception->getMessage() !== '' ? $exception->getMessage() : __('general.api.errors.forbidden'),
                403,
            );
        });

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make(
                $request,
                'validation_failed',
                __('general.api.errors.validation_failed'),
                422,
                errors: $exception->errors(),
            );
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'resource_not_found', __('general.api.errors.resource_not_found'), 404);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'resource_not_found', __('general.api.errors.resource_not_found'), 404);
        });

        $exceptions->render(function (InvalidAdminCredentials $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'invalid_admin_credentials', $exception->getMessage(), 422);
        });

        $exceptions->render(function (CatalogResourceInUse $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'catalog_resource_in_use', $exception->getMessage(), 422);
        });

        $exceptions->render(function (
            CartInvalidProductReference|CatalogInvalidProductReference $exception,
            Request $request
        ): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'invalid_product_reference', $exception->getMessage(), 404);
        });

        $exceptions->render(function (InvalidCartQuantity $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'invalid_cart_quantity', $exception->getMessage(), 422);
        });

        $exceptions->render(function (EmptyCart $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'empty_cart', $exception->getMessage(), 422);
        });

        $exceptions->render(function (InvalidOrderContact $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'invalid_order_contact', $exception->getMessage(), 422);
        });

        $exceptions->render(function (InsufficientStock $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'insufficient_stock', $exception->getMessage(), 422);
        });

        $exceptions->render(function (InvalidProductData $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'invalid_product_data', $exception->getMessage(), 422);
        });

        $exceptions->render(function (ProductImageStorageFailed $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiProblemDetails::make($request, 'product_image_storage_failed', $exception->getMessage(), 500);
        });
    })->create();
