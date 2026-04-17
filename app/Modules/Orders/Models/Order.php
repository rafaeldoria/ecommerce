<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING_FULFILLMENT = 'pending_fulfillment';

    protected $fillable = [
        'email',
        'whatsapp',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
