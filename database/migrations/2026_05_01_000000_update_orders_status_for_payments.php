<?php

use App\Modules\Orders\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'pending_fulfillment')
            ->update(['status' => OrderStatus::Pending->value]);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', OrderStatus::Pending->value)
            ->update(['status' => 'pending_fulfillment']);
    }
};
