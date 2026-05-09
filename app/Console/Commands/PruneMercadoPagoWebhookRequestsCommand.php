<?php

namespace App\Console\Commands;

use App\Modules\Payments\Actions\PruneMercadoPagoWebhookRequestsAction;
use Illuminate\Console\Command;

class PruneMercadoPagoWebhookRequestsCommand extends Command
{
    protected $signature = 'payments:prune-mercado-pago-webhooks';

    protected $description = 'Prune old Mercado Pago webhook journal rows';

    public function handle(PruneMercadoPagoWebhookRequestsAction $action): int
    {
        $retentionDays = (int) config('security.mercado_pago_webhook_retention_days', 90);
        $deleted = $action->execute($retentionDays);

        $this->info("Deleted {$deleted} Mercado Pago webhook journal rows.");

        return self::SUCCESS;
    }
}
