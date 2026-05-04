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
            $table->string('provider');
            $table->string('provider_preference_id')->nullable()->index();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('external_reference')->unique();
            $table->text('checkout_url')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('status');
            $table->string('provider_status')->nullable();
            $table->string('provider_status_detail')->nullable();
            $table->json('raw_provider_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
