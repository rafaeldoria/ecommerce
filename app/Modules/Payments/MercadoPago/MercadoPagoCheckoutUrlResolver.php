<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;

class MercadoPagoCheckoutUrlResolver
{
    public const INIT_POINT = 'init_point';

    public const SANDBOX_INIT_POINT = 'sandbox_init_point';

    public function resolve(object $preference): ?string
    {
        return match ($this->strategy()) {
            self::INIT_POINT => $this->stringOrNull($preference->init_point ?? null),
            self::SANDBOX_INIT_POINT => $this->stringOrNull($preference->sandbox_init_point ?? null)
                ?? $this->stringOrNull($preference->init_point ?? null),
        };
    }

    private function strategy(): string
    {
        $strategy = strtolower(trim((string) config('services.mercado_pago.checkout_url_strategy', self::INIT_POINT)));

        if (!in_array($strategy, [self::INIT_POINT, self::SANDBOX_INIT_POINT], true)) {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_invalid'));
        }

        return $strategy;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
