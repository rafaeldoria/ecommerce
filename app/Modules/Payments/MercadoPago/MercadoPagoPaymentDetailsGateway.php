<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\MercadoPagoPaymentDetails;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoPaymentDetailsGateway implements PaymentDetailsGateway
{
    public function get(string $paymentId): MercadoPagoPaymentDetails
    {
        $accessToken = (string) config('services.mercado_pago.access_token', '');
        $environment = (string) config('services.mercado_pago.env', 'sandbox');

        if ($accessToken === '') {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_missing'));
        }

        MercadoPagoConfig::setAccessToken($accessToken);

        if ($environment === 'sandbox' || app()->environment('local', 'testing')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }

        $payment = (new PaymentClient)->get((int) $paymentId);

        return new MercadoPagoPaymentDetails(
            paymentId: (string) $payment->id,
            externalReference: (string) $payment->external_reference,
            status: $payment->status,
            statusDetail: $payment->status_detail,
            amountCents: (int) round(((float) $payment->transaction_amount) * 100),
            metadata: [
                'date_created' => $payment->date_created,
                'date_last_updated' => $payment->date_last_updated,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
                'currency_id' => $payment->currency_id,
                'live_mode' => $payment->live_mode,
            ],
        );
    }
}
