<?php

namespace App\Modules\Orders\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Error = 'error';
    case Completed = 'completed';
}
