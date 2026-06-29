<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_reference', 120);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('allocated');
            $table->string('idempotency_key', 160)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index(['target_type', 'target_reference', 'status'], 'payment_allocations_target_status_idx');
            $table->unique(
                ['payment_id', 'target_type', 'target_reference'],
                'payment_allocations_payment_target_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
