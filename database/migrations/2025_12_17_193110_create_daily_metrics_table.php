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
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('orders_count')->default(0);
            $table->integer('orders_completed')->default(0);
            $table->integer('orders_cancelled')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->integer('deliveries_completed')->default(0);
            $table->decimal('avg_delivery_time', 8, 2)->nullable(); // Minutes
            $table->integer('payments_paid')->default(0);
            $table->integer('payments_failed')->default(0);
            $table->decimal('payment_success_rate', 5, 2)->nullable(); // Pourcentage
            $table->integer('new_users')->default(0);
            $table->integer('active_restaurants')->default(0);
            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
