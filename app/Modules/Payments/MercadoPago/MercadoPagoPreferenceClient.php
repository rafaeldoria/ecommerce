<?php

namespace App\Modules\Payments\MercadoPago;

use MercadoPago\Client\Preference\PreferenceClient;

class MercadoPagoPreferenceClient
{
    public function create(array $request): object
    {
        return (new PreferenceClient)->create($request);
    }
}
