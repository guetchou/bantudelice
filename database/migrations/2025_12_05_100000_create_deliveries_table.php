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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            
            $table->enum('status', [
                'PENDING',      // créée, non assignée
                'ASSIGNED',     // assignée à un livreur
                'PICKED_UP',    // récupérée au restaurant
                'ON_THE_WAY',   // en route
                'DELIVERED',    // livrée
                'CANCELLED',    // annulée
            ])->default('PENDING');
            
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->unsignedInteger('delivery_fee')->default(0); // aligné sur la commande
            $table->timestamps();
            
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            
            $table->index('status');
            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};

