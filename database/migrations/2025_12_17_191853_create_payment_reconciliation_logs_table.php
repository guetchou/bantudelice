<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->string('status', 50); // VERIFIED, RECONCILED, INCONSISTENT, FAILED, ERROR
            $table->text('message')->nullable();
            $table->string('provider', 50);
            $table->string('provider_reference')->nullable();
            $table->integer('amount');
            $table->timestamps();

            // Foreign key optionnelle (si table payments existe)
            if (Schema::hasTable('payments')) {
                $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            }
            $table->index(['payment_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_logs');
    }
};
