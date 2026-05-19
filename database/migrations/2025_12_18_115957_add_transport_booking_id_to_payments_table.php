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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'transport_booking_id')) {
                $table->foreignId('transport_booking_id')->nullable()->after('shipment_id')->constrained('transport_bookings')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'transport_booking_id')) {
                $table->dropForeign(['transport_booking_id']);
                $table->dropColumn('transport_booking_id');
            }
        });
    }
};
