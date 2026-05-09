<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Models\MercadoPagoWebhookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneMercadoPagoWebhookRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prunes_webhook_journal_rows_older_than_the_retention_window(): void
    {
        config(['security.mercado_pago_webhook_retention_days' => 30]);

        MercadoPagoWebhookRequest::query()->create([
            'received_at' => now()->subDays(31),
            'processing_status' => 'failed',
        ]);

        MercadoPagoWebhookRequest::query()->create([
            'received_at' => now()->subDays(10),
            'processing_status' => 'processed',
        ]);

        $this->artisan('payments:prune-mercado-pago-webhooks')
            ->expectsOutput('Deleted 1 Mercado Pago webhook journal rows.')
            ->assertExitCode(0);

        $this->assertDatabaseCount((new MercadoPagoWebhookRequest)->getTable(), 1);
    }
}
