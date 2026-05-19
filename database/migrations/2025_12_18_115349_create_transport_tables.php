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
        // 1. Transport Vehicles (for Rental and Driver Assignment)
        if (!Schema::hasTable('transport_vehicles')) {
            Schema::create('transport_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('make');
                $table->string('model');
                $table->string('year');
                $table->string('plate_number')->unique();
                $table->string('color');
                $table->string('type'); // taxi, carpool, rental
                $table->integer('seats')->default(4);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->json('features')->nullable(); // AC, GPS, etc.
                $table->decimal('daily_rate', 10, 2)->nullable(); // For rental
                $table->boolean('is_available')->default(true);
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Pricing Rules
        if (!Schema::hasTable('transport_pricing_rules')) {
            Schema::create('transport_pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // taxi, carpool, rental
                $table->string('zone')->nullable();
                $table->decimal('base_fare', 10, 2)->default(0);
                $table->decimal('price_per_km', 10, 2)->default(0);
                $table->decimal('price_per_minute', 10, 2)->default(0);
                $table->decimal('minimum_fare', 10, 2)->default(0);
                $table->decimal('surge_multiplier', 4, 2)->default(1.0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Transport Requests / Bookings (Unified table)
        if (!Schema::hasTable('transport_bookings')) {
            Schema::create('transport_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('booking_no')->unique();
                $table->string('type'); // taxi, carpool, rental
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
                $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->onDelete('set null');
                $table->foreignId('ride_id')->nullable(); // For carpool link
                
                // Itinerary / Pickup
                $table->string('pickup_address')->nullable();
                $table->decimal('pickup_lat', 10, 8)->nullable();
                $table->decimal('pickup_lng', 11, 8)->nullable();
                $table->string('dropoff_address')->nullable();
                $table->decimal('dropoff_lat', 10, 8)->nullable();
                $table->decimal('dropoff_lng', 11, 8)->nullable();
                
                // Timing
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                
                // Rental specific
                $table->dateTime('return_date')->nullable();
                
                // Financials
                $table->decimal('estimated_distance', 8, 2)->nullable(); // in km
                $table->integer('estimated_duration')->nullable(); // in minutes
                $table->decimal('estimated_price', 10, 2)->nullable();
                $table->decimal('actual_price', 10, 2)->nullable();
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2)->nullable();
                
                $table->string('payment_method')->nullable();
                $table->string('payment_status')->default('pending');
                $table->string('status')->default('requested');
                
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['user_id', 'status']);
                $table->index(['driver_id', 'status']);
                $table->index('type');
                $table->index('created_at');
            });
        }

        // 4. Carpool Rides (Published by drivers)
        if (!Schema::hasTable('transport_rides')) {
            Schema::create('transport_rides', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
                $table->foreignId('vehicle_id')->constrained('transport_vehicles')->onDelete('cascade');
                
                $table->string('origin_address');
                $table->decimal('origin_lat', 10, 8);
                $table->decimal('origin_lng', 11, 8);
                $table->string('destination_address');
                $table->decimal('destination_lat', 10, 8);
                $table->decimal('destination_lng', 11, 8);
                
                $table->dateTime('departure_time');
                $table->integer('available_seats');
                $table->decimal('price_per_seat', 10, 2);
                
                $table->string('status')->default('published');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 5. Tracking points
        if (!Schema::hasTable('transport_tracking_points')) {
            Schema::create('transport_tracking_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('transport_bookings')->onDelete('cascade');
                $table->decimal('lat', 10, 8);
                $table->decimal('lng', 11, 8);
                $table->decimal('speed', 5, 2)->nullable();
                $table->timestamp('recorded_at');
                
                $table->index(['booking_id', 'recorded_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_tracking_points');
        Schema::dropIfExists('transport_rides');
        Schema::dropIfExists('transport_bookings');
        Schema::dropIfExists('transport_pricing_rules');
        Schema::dropIfExists('transport_vehicles');
    }
};
