<?php

namespace App\Modules\Payments\MercadoPago;

use Illuminate\Support\Arr;

class MercadoPagoPayloadSanitizer
{
    /**
     * @return array<string, mixed>
     */
    public function preferenceSnapshot(array $payload): array
    {
        return $this->onlyPaths($payload, [
            'id',
            'external_reference',
            'date_created',
            'expiration_date_from',
            'expiration_date_to',
            'expires',
            'items',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentSnapshot(array $payload): array
    {
        return $this->onlyPaths($payload, [
            'id',
            'external_reference',
            'status',
            'status_detail',
            'transaction_amount',
            'currency_id',
            'date_created',
            'date_approved',
            'date_last_updated',
            'payment_method_id',
            'payment_type_id',
            'operation_type',
            'live_mode',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function webhookPayload(array $payload): array
    {
        return $this->onlyPaths($payload, [
            'action',
            'api_version',
            'data.id',
            'date_created',
            'id',
            'live_mode',
            'type',
            'user_id',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function webhookQuery(array $query): array
    {
        return $this->onlyPaths($query, [
            'data.id',
            'data_id',
            'id',
            'topic',
            'type',
        ]);
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<string, mixed>
     */
    private function onlyPaths(array $payload, array $paths): array
    {
        $sanitized = [];

        foreach ($paths as $path) {
            if (array_key_exists($path, $payload)) {
                $sanitized[$path] = $payload[$path];

                continue;
            }

            if (!Arr::has($payload, $path)) {
                continue;
            }

            Arr::set($sanitized, $path, Arr::get($payload, $path));
        }

        return $sanitized;
    }
}
