<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\DTOs\CheckoutPreferenceData;

class MercadoPagoPreferenceRequestFactory
{
    public function create(CheckoutPreferenceData $data): array
    {
        $request = [
            'items' => $data->items,
            'payer' => [
                'email' => $data->email,
            ],
            'payment_methods' => [
                'excluded_payment_methods' => [],
                'installments' => 12,
                'default_installments' => 1,
            ],
            'back_urls' => $data->backUrls,
            'statement_descriptor' => (string) config('services.mercado_pago.statement_descriptor', 'GRSHOP'),
            'external_reference' => $data->externalReference,
            'expires' => false,
        ];

        if ($data->notificationUrl !== null && trim($data->notificationUrl) !== '') {
            $request['notification_url'] = trim($data->notificationUrl);
        }

        if ($this->supportsAutoReturn($data->backUrls['success'])) {
            $request['auto_return'] = 'approved';
        }

        return $request;
    }

    private function supportsAutoReturn(string $successUrl): bool
    {
        $host = parse_url($successUrl, PHP_URL_HOST);

        return is_string($host)
            && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
