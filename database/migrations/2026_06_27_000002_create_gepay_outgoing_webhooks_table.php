<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_outgoing_webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('delivery_id')->unique();
            $table->foreignId('client_id')->constrained('gepay_clients')->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('gepay_transactions')->restrictOnDelete();
            $table->string('event', 64)->index();
            $table->string('webhook_url', 500);
            $table->longText('payload')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('last_attempted_at')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_outgoing_webhooks');
    }
};
