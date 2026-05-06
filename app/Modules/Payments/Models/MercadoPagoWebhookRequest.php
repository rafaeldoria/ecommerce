<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoWebhookRequest extends Model
{
    protected $fillable = [
        'received_at',
        'processing_status',
        'http_status_returned',
        'event_type',
        'event_action',
        'notification_id',
        'data_id',
        'live_mode',
        'user_id',
        'x_request_id',
        'x_signature',
        'signature_ts',
        'signature_hash',
        'signature_valid',
        'signature_manifest',
        'validation_error',
        'headers',
        'query',
        'payload',
        'related_payment_id',
        'provider_payment_id',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'received_at' => 'immutable_datetime',
            'http_status_returned' => 'integer',
            'live_mode' => 'boolean',
            'signature_valid' => 'boolean',
            'headers' => 'array',
            'query' => 'array',
            'payload' => 'array',
            'related_payment_id' => 'integer',
            'processed_at' => 'immutable_datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'related_payment_id');
    }
}
