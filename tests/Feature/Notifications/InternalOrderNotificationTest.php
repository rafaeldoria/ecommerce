<?php

namespace Tests\Feature\Notifications;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Listeners\NotifyInternalTeamOfCreatedOrder;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Notifications\InternalOrderCreatedNotification;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternalOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_created_event_queues_the_internal_notification_listener(): void
    {
        Queue::fake();

        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
            'status' => Order::STATUS_PENDING_FULFILLMENT,
        ]);

        event(new OrderCreated($order));

        Queue::assertPushed(CallQueuedListener::class, static function (CallQueuedListener $job): bool {
            return $job->class === NotifyInternalTeamOfCreatedOrder::class;
        });
    }

    #[Test]
    public function listener_sends_an_on_demand_mail_notification_to_the_internal_team(): void
    {
        Notification::fake();

        config()->set('services.internal_orders.email', 'ops@example.com');

        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
            'status' => Order::STATUS_PENDING_FULFILLMENT,
        ]);

        $product = Product::factory()->create([
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
        ]);

        app(NotifyInternalTeamOfCreatedOrder::class)->handle(new OrderCreated($order->fresh('items')));

        Notification::assertSentOnDemand(InternalOrderCreatedNotification::class, static function (
            InternalOrderCreatedNotification $notification,
            array $channels,
            object $notifiable,
        ): bool {
            return in_array('mail', $channels, true)
                && $notifiable->routeNotificationFor('mail') === 'ops@example.com';
        });
    }

    #[Test]
    public function listener_logs_a_masked_fallback_when_no_internal_recipient_is_configured(): void
    {
        Log::spy();

        config()->set('services.internal_orders.email', null);
        config()->set('mail.from.address', null);

        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 98888-7777',
            'status' => Order::STATUS_PENDING_FULFILLMENT,
        ]);

        app(NotifyInternalTeamOfCreatedOrder::class)->handle(new OrderCreated($order));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(static function (string $message, array $context) use ($order): bool {
                return $message === __('general.logs.internal_order_notification_fallback')
                    && $context['order_id'] === $order->getKey()
                    && $context['buyer_email'] !== $order->email
                    && $context['buyer_whatsapp'] !== $order->whatsapp;
            });
    }
}
