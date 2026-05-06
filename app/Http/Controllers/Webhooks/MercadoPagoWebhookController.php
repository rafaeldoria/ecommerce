<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Actions\HandleMercadoPagoWebhookAction;
use App\Modules\Payments\DTOs\MercadoPagoWebhookRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, HandleMercadoPagoWebhookAction $action): JsonResponse
    {
        $result = $action->execute(new MercadoPagoWebhookRequestData(
            headers: $this->sanitizedHeaders($request),
            query: $this->queryParams($request),
            payload: $this->jsonPayload($request),
        ));

        return response()->json(['status' => $result->status], $result->httpStatus);
    }

    private function sanitizedHeaders(Request $request): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'php-auth-pw',
            'x-csrf-token',
            'x-xsrf-token',
        ];

        return collect($request->headers->all())
            ->mapWithKeys(function (array $values, string $key) use ($sensitiveHeaders): array {
                $key = strtolower($key);

                if (in_array($key, $sensitiveHeaders, true)) {
                    return [];
                }

                return [
                    $key => array_map(
                        static fn (string $value): string => Str::limit($value, 1000, ''),
                        $values,
                    ),
                ];
            })
            ->all();
    }

    private function jsonPayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }

    private function queryParams(Request $request): array
    {
        $rawQueryString = (string) $request->server->get('QUERY_STRING', '');

        if ($rawQueryString === '') {
            return [];
        }

        $query = [];

        foreach (explode('&', $rawQueryString) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            $key = urldecode($key);
            $value = urldecode($value);

            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $query)) {
                $query[$key] = array_merge((array) $query[$key], [$value]);

                continue;
            }

            $query[$key] = $value;
        }

        return $query;
    }
}
