<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoCheckoutPreferenceGateway implements CheckoutPreferenceGateway
{
    public function __construct(
        private readonly MercadoPagoPreferenceRequestFactory $requestFactory,
    ) {}

    public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
    {
        $accessToken = (string) config('services.mercado_pago.access_token', '');
        $publicKey = (string) config('services.mercado_pago.public_key', '');
        $environment = (string) config('services.mercado_pago.env', 'sandbox');

        if ($accessToken === '' || $publicKey === '') {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_missing'));
        }

        MercadoPagoConfig::setAccessToken($accessToken);

        if ($environment === 'sandbox' || app()->environment('local', 'testing')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }

        $preference = (new PreferenceClient)->create($this->requestFactory->create($data));
        $checkoutUrl = $environment === 'sandbox'
            ? ($preference->sandbox_init_point ?? $preference->init_point)
            : $preference->init_point;

        return new CheckoutPreferenceResult(
            preferenceId: (string) $preference->id,
            publicKey: $publicKey,
            checkoutUrl: $checkoutUrl,
        );
    }
}
