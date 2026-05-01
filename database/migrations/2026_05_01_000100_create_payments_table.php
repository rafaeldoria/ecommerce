<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('mercado_pago_preference_id')->nullable()->unique();
            $table->text('mercado_pago_checkout_url')->nullable();
            $table->string('mercado_pago_payment_id')->nullable()->unique();
            $table->string('external_reference')->unique();
            $table->string('status')->nullable();
            $table->string('status_detail')->nullable();
            $table->bigInteger('amount_cents');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
