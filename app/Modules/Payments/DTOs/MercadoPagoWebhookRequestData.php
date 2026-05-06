<?php

namespace App\Modules\Payments\DTOs;

readonly class MercadoPagoWebhookRequestData
{
    public function __construct(
        public array $headers,
        public array $query,
        public array $payload,
    ) {}

    public function header(string $name): ?string
    {
        $value = $this->headers[strtolower($name)] ?? null;

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
