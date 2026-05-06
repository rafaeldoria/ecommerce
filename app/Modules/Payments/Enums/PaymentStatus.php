<?php

namespace App\Modules\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case ChargedBack = 'charged_back';
    case Unknown = 'unknown';
}
