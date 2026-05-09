<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;

class MercadoPagoCheckoutUrlResolver
{
    public const INIT_POINT = 'init_point';

    public const SANDBOX_INIT_POINT = 'sandbox_init_point';

    public function resolve(object $preference): ?string
    {
        $url = match ($this->strategy()) {
            self::INIT_POINT => $this->stringOrNull($preference->init_point ?? null),
            self::SANDBOX_INIT_POINT => $this->stringOrNull($preference->sandbox_init_point ?? null)
                ?? $this->stringOrNull($preference->init_point ?? null),
        };

        if ($url === null) {
            return null;
        }

        if (!$this->hostIsAllowed($url)) {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_invalid'));
        }

        return $url;
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

    private function hostIsAllowed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            return false;
        }

        $allowedHosts = config('services.mercado_pago.checkout_allowed_hosts', []);

        if (!is_array($allowedHosts)) {
            return false;
        }

        return in_array(strtolower($host), array_map('strtolower', $allowedHosts), true);
    }
}
