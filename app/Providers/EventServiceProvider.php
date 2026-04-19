<?php

namespace App\Providers;

use App\Modules\Orders\Events\OrderCreated;
use App\Modules\Orders\Listeners\NotifyInternalTeamOfCreatedOrder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            NotifyInternalTeamOfCreatedOrder::class,
        ],
    ];
}
