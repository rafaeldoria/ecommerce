<?php

namespace App\Modules\Payments\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'mercado_pago_preference_id',
        'mercado_pago_checkout_url',
        'mercado_pago_payment_id',
        'external_reference',
        'status',
        'status_detail',
        'amount_cents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'order_id' => 'integer',
            'amount_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
