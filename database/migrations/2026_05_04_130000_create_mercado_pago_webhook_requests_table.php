<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercado_pago_webhook_requests', function (Blueprint $table): void {
            $table->id();
            $table->timestampTz('received_at')->useCurrent()->index();
            $table->string('processing_status')->default('received')->index();
            $table->unsignedSmallInteger('http_status_returned')->nullable();
            $table->string('event_type')->nullable()->index();
            $table->string('event_action')->nullable();
            $table->string('notification_id')->nullable()->index();
            $table->string('data_id')->nullable()->index();
            $table->boolean('live_mode')->nullable();
            $table->string('user_id')->nullable()->index();
            $table->string('x_request_id')->nullable()->index();
            $table->text('x_signature')->nullable();
            $table->string('signature_ts')->nullable();
            $table->string('signature_hash', 128)->nullable();
            $table->boolean('signature_valid')->nullable()->index();
            $table->text('signature_manifest')->nullable();
            $table->text('validation_error')->nullable();
            $table->json('headers')->nullable();
            $table->json('query')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('related_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider_payment_id')->nullable()->index();
            $table->timestampTz('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_webhook_requests');
    }
};
