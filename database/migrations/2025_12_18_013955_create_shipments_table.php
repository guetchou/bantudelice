<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tracking_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('status'); // Managed by ShipmentStatus Enum
            $table->string('service_level')->default('standard'); // standard|express
            $table->string('pickup_type')->default('door'); // door|relay
            $table->string('dropoff_type')->default('door'); // door|relay
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->decimal('cod_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2);
            $table->bigInteger('volume_cm3')->nullable();
            $table->json('price_breakdown');
            $table->decimal('total_price', 12, 2);
            $table->string('payment_status')->default('unpaid'); // unpaid|paid|cod_pending|refunded
            $table->foreignId('assigned_courier_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('customer_id');
            $table->index('assigned_courier_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

