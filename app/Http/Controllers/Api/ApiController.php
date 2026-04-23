<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Exceptions\InvalidAdminCredentials;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Cart\Exceptions\InvalidCartQuantity;
use App\Modules\Cart\Exceptions\InvalidProductReference as CartInvalidProductReference;
use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference as CatalogInvalidProductReference;
use App\Modules\Orders\Exceptions\InsufficientStock;
use App\Modules\Orders\Exceptions\InvalidOrderContact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

abstract class ApiController extends Controller
{
    protected function respond(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (CartInvalidProductReference|CatalogInvalidProductReference $exception) {
            return $this->error($exception->getMessage(), 'invalid_product_reference', 404);
        } catch (InvalidCartQuantity $exception) {
            return $this->error($exception->getMessage(), 'invalid_cart_quantity', 422);
        } catch (EmptyCart $exception) {
            return $this->error($exception->getMessage(), 'empty_cart', 422);
        } catch (InvalidOrderContact $exception) {
            return $this->error($exception->getMessage(), 'invalid_order_contact', 422);
        } catch (InsufficientStock $exception) {
            return $this->error($exception->getMessage(), 'insufficient_stock', 422);
        } catch (InvalidProductData $exception) {
            return $this->error($exception->getMessage(), 'invalid_product_data', 422);
        } catch (CatalogResourceInUse $exception) {
            return $this->error($exception->getMessage(), 'catalog_resource_in_use', 422);
        } catch (InvalidAdminCredentials $exception) {
            return $this->error($exception->getMessage(), 'invalid_admin_credentials', 422);
        } catch (ModelNotFoundException) {
            return $this->error(__('general.api.errors.resource_not_found'), 'resource_not_found', 404);
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    protected function error(string $message, string $error, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => $error,
        ], $status);
    }
}
