<?php

namespace App\Modules\Payments\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_preference_id',
        'provider_payment_id',
        'external_reference',
        'checkout_url',
        'amount_cents',
        'currency',
        'status',
        'provider_status',
        'provider_status_detail',
        'raw_provider_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'order_id' => 'integer',
            'amount_cents' => 'integer',
            'raw_provider_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
