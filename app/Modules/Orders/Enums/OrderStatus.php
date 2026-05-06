<?php

namespace App\Modules\Orders\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentFailed = 'payment_failed';
    case Paid = 'paid';
    case PendingFulfillment = 'pending_fulfillment';
}
