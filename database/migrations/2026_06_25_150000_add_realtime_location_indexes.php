<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRealtimeLocationIndexes extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_locations')) {
            Schema::table('driver_locations', function (Blueprint $table) {
                $table->index(['driver_id', 'timestamp', 'id'], 'driver_locations_driver_timestamp_id_index');
            });
        }

        if (Schema::hasTable('transport_tracking_points')) {
            Schema::table('transport_tracking_points', function (Blueprint $table) {
                $table->index(['booking_id', 'recorded_at', 'id'], 'transport_tracking_points_booking_recorded_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transport_tracking_points')) {
            Schema::table('transport_tracking_points', function (Blueprint $table) {
                $table->dropIndex('transport_tracking_points_booking_recorded_id_index');
            });
        }

        if (Schema::hasTable('driver_locations')) {
            Schema::table('driver_locations', function (Blueprint $table) {
                $table->dropIndex('driver_locations_driver_timestamp_id_index');
            });
        }
    }
}
