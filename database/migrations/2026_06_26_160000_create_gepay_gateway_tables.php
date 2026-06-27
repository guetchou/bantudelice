<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('api_key', 96)->unique();
            $table->text('api_secret');
            $table->json('capabilities');
            $table->json('allowed_ips')->nullable();
            $table->string('webhook_url', 500)->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('gepay_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained('gepay_clients')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('provider', 64)->index();
            $table->string('external_reference', 191);
            $table->string('provider_reference', 191)->nullable()->unique();
            $table->string('idempotency_key', 191);
            $table->char('request_hash', 64);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->text('phone');
            $table->string('phone_masked', 64);
            $table->string('status', 32)->index();
            $table->string('failure_code', 191)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->longText('provider_metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['client_id', 'idempotency_key'], 'gepay_client_idempotency_unique');
            $table->unique(['client_id', 'type', 'external_reference'], 'gepay_client_type_external_unique');
        });

        Schema::create('gepay_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 64)->index();
            $table->string('event_key', 191)->nullable()->index();
            $table->char('payload_hash', 64);
            $table->string('status', 32)->default('received')->index();
            $table->longText('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'payload_hash'], 'gepay_provider_payload_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_webhook_events');
        Schema::dropIfExists('gepay_transactions');
        Schema::dropIfExists('gepay_clients');
    }
};
