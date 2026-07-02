<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained('gepay_wallets')->restrictOnDelete();
            $table->string('type', 64);
            // Strictly positive and non-null — enforced at model level; DB trigger added in migration 11
            $table->bigInteger('amount');
            $table->string('source_bucket', 32)->nullable();
            $table->string('destination_bucket', 32)->nullable();
            // NOT NULL: every entry must reference its originating entity
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->string('idempotency_key', 191);
            $table->json('metadata')->nullable();
            // Required for adjustment_* entries; nullable for all others
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — immutable journal

            $table->unique(['merchant_id', 'type', 'idempotency_key'], 'gepay_ledger_merchant_type_ikey_unique');
            $table->index(['merchant_id', 'type']);
            $table->index(['merchant_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_ledger_entries');
    }
};
