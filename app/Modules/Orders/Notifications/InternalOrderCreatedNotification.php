<?php

namespace App\Modules\Orders\Notifications;

use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternalOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalAmount = $this->order->items->sum(
            static fn ($item): int => $item->quantity * $item->unit_price,
        );

        $mail = (new MailMessage)
            ->subject(__('general.notifications.internal_order_created.subject', [
                'order_id' => $this->order->getKey(),
            ]))
            ->line(__('general.notifications.internal_order_created.intro'))
            ->line(__('general.notifications.internal_order_created.order_status', [
                'status' => $this->order->status,
            ]))
            ->line(__('general.notifications.internal_order_created.buyer_email', [
                'email' => $this->order->email,
            ]))
            ->line(__('general.notifications.internal_order_created.buyer_whatsapp', [
                'whatsapp' => $this->order->whatsapp,
            ]))
            ->line(__('general.notifications.internal_order_created.order_total', [
                'amount' => $totalAmount,
            ]));

        foreach ($this->order->items as $item) {
            $mail->line(__('general.notifications.internal_order_created.item_line', [
                'product' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
            ]));
        }

        return $mail;
    }
}
