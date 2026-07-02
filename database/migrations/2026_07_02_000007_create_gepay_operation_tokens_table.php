<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_operation_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('token', 36)->unique();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('gepay_merchant_users')->restrictOnDelete();
            $table->string('operation_type', 32);
            $table->char('request_hash', 64)->nullable();
            $table->string('operation_ref', 191)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            // No updated_at — tokens are written once and stamped on use

            $table->index(['merchant_id', 'operation_type']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_operation_tokens');
    }
};
