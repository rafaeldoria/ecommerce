<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProblemDetails
{
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function make(
        Request $request,
        string $code,
        string $detail,
        int $status,
        ?string $title = null,
        array $errors = [],
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'type' => '/problems/'.str_replace('_', '-', $code),
            'title' => $title ?? self::titleFor($code),
            'status' => $status,
            'detail' => $detail,
            'code' => strtoupper($code),
            'instance' => '/'.ltrim($request->path(), '/'),
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()
            ->json($payload, $status)
            ->header('Content-Type', 'application/problem+json');
    }

    private static function titleFor(string $code): string
    {
        return match ($code) {
            'validation_failed' => 'Validation failed',
            'unauthenticated' => 'Unauthenticated',
            'forbidden' => 'Forbidden',
            'resource_not_found' => 'Resource not found',
            'invalid_admin_credentials' => 'Invalid admin credentials',
            'catalog_resource_in_use' => 'Catalog resource in use',
            'invalid_product_reference' => 'Invalid product reference',
            'invalid_cart_quantity' => 'Invalid cart quantity',
            'empty_cart' => 'Empty cart',
            'invalid_order_contact' => 'Invalid order contact',
            'insufficient_stock' => 'Insufficient stock',
            'invalid_product_data' => 'Invalid product data',
            'product_image_storage_failed' => 'Product image storage failed',
            default => 'API error',
        };
    }
}
