<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoCheckoutPreferenceGateway implements CheckoutPreferenceGateway
{
    public function __construct(
        private readonly MercadoPagoPreferenceRequestFactory $requestFactory,
        private readonly MercadoPagoPreferenceClient $preferenceClient,
        private readonly MercadoPagoCheckoutUrlResolver $checkoutUrlResolver,
    ) {}

    public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
    {
        $accessToken = trim((string) config('services.mercado_pago.access_token', ''));
        $publicKey = trim((string) config('services.mercado_pago.public_key', ''));
        $credentialMode = $this->credentialMode();

        if ($accessToken === '' || $publicKey === '') {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_missing'));
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(
            $credentialMode === 'test' ? MercadoPagoConfig::LOCAL : MercadoPagoConfig::SERVER,
        );

        $preference = $this->preferenceClient->create($this->requestFactory->create($data));

        return new CheckoutPreferenceResult(
            preferenceId: (string) $preference->id,
            publicKey: $publicKey,
            checkoutUrl: $this->checkoutUrlResolver->resolve($preference),
        );
    }

    private function credentialMode(): string
    {
        $credentialMode = strtolower(trim((string) config('services.mercado_pago.credential_mode', 'test')));

        if (!in_array($credentialMode, ['test', 'production'], true)) {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_invalid'));
        }

        return $credentialMode;
    }
}
