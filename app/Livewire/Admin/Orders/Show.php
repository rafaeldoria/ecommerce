<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Queries\GetAdminOrderQuery;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Support\MoneyFormatter;
use DateTimeInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    use UsesLocalizedPageTitle;

    public int $order;

    public Order $foundOrder;

    public function mount(int $order, GetAdminOrderQuery $getAdminOrderQuery): void
    {
        $this->order = $order;
        $this->foundOrder = $getAdminOrderQuery->execute($order);
    }

    public function render()
    {
        $totalAmount = $this->foundOrder->items->sum(
            static fn ($item): int => $item->unit_price * $item->quantity
        );
        $latestPayment = $this->foundOrder->payments->first();
        $latestWebhook = $latestPayment?->webhookRequests->first();

        return $this->pageView('livewire.admin.orders.show', [
            'formattedTotalAmount' => MoneyFormatter::brlFromCents($totalAmount),
            'latestPayment' => $latestPayment,
            'latestWebhook' => $latestWebhook,
            'manualFulfillmentAllowed' => $this->foundOrder->status === OrderStatus::Paid->value
                && $latestPayment?->status === PaymentStatus::Approved->value,
            'formattedPaymentUpdatedAt' => $this->formatDateTime($latestPayment?->updated_at),
            'formattedWebhookReceivedAt' => $this->formatDateTime($latestWebhook?->received_at),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.orders.detail_title';
    }

    private function formatDateTime(?DateTimeInterface $dateTime): string
    {
        if ($dateTime === null) {
            return '-';
        }

        return $dateTime->format('Y-m-d H:i');
    }
}
