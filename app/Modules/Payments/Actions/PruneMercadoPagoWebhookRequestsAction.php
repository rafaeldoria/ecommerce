<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Models\MercadoPagoWebhookRequest;

class PruneMercadoPagoWebhookRequestsAction
{
    public function execute(int $retentionDays): int
    {
        if ($retentionDays < 1) {
            return 0;
        }

        return MercadoPagoWebhookRequest::query()
            ->where('received_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}
