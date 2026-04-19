<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Notifications\InternalOrderCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyInternalTeamOfCreatedOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        $order = $event->order->loadMissing('items');
        $recipient = config('services.internal_orders.email') ?: config('mail.from.address');

        if (filled($recipient)) {
            Notification::route('mail', $recipient)
                ->notify(new InternalOrderCreatedNotification($order));

            return;
        }

        Log::warning(__('general.logs.internal_order_notification_fallback'), [
            'order_id' => $order->getKey(),
            'buyer_email' => $this->maskEmail($order->email),
            'buyer_whatsapp' => $this->maskPhone($order->whatsapp),
            'item_count' => $order->items->count(),
        ]);
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error(__('general.logs.queued_order_notification_failed'), [
            'order_id' => $event->order->getKey(),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return '***';
        }

        return substr($localPart, 0, 1).'***@'.$domain;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '***';
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
