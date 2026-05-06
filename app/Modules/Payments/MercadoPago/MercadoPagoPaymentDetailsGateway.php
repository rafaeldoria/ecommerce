<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\ProviderPaymentDetails;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use Illuminate\Support\Facades\Http;

class MercadoPagoPaymentDetailsGateway implements PaymentDetailsGateway
{
    public function find(string $providerPaymentId): ProviderPaymentDetails
    {
        $accessToken = trim((string) config('services.mercado_pago.access_token', ''));

        if ($accessToken === '') {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_missing'));
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://api.mercadopago.com/v1/payments/'.urlencode($providerPaymentId))
            ->throw()
            ->json();

        $payload = is_array($response) ? $response : [];

        return new ProviderPaymentDetails(
            providerPaymentId: $this->stringValue($payload['id'] ?? $providerPaymentId) ?? $providerPaymentId,
            externalReference: $this->stringValue($payload['external_reference'] ?? null),
            status: $this->stringValue($payload['status'] ?? null),
            statusDetail: $this->stringValue($payload['status_detail'] ?? null),
            amountCents: $this->amountCents($payload['transaction_amount'] ?? null),
            currency: $this->stringValue($payload['currency_id'] ?? null),
            rawProviderResponse: $payload,
        );
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function amountCents(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }
}
