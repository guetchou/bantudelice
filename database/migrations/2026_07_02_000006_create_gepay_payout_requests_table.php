<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained('gepay_wallets')->restrictOnDelete();
            $table->foreignId('payout_destination_id')->constrained('gepay_payout_destinations')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->text('destination_snapshot');
            $table->string('status', 32)->default('draft')->index();
            $table->string('idempotency_key', 191);
            // Non-null as soon as execution is initiated (submitted→processing)
            $table->unsignedBigInteger('execution_transaction_id')->nullable();
            $table->string('processed_by', 191)->nullable();
            $table->string('operator_reference', 191)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'idempotency_key'], 'gepay_payout_merchant_ikey_unique');
            $table->index(['merchant_id', 'status']);

            $table->foreign('execution_transaction_id')
                ->references('id')
                ->on('gepay_transactions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_payout_requests');
    }
};
