<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpsFieldsToTransportBookingsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('transport_bookings')) {
            return;
        }

        Schema::table('transport_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_bookings', 'driver_arrived_at')) {
                $table->dateTime('driver_arrived_at')->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('transport_bookings', 'picked_up_at')) {
                $table->dateTime('picked_up_at')->nullable()->after('driver_arrived_at');
            }
            if (!Schema::hasColumn('transport_bookings', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('transport_bookings', 'cash_collected_at')) {
                $table->dateTime('cash_collected_at')->nullable()->after('closed_at');
            }
            if (!Schema::hasColumn('transport_bookings', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('transport_bookings', 'last_status_changed_at')) {
                $table->dateTime('last_status_changed_at')->nullable()->after('cancel_reason');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('transport_bookings')) {
            return;
        }

        Schema::table('transport_bookings', function (Blueprint $table) {
            foreach ([
                'driver_arrived_at',
                'picked_up_at',
                'closed_at',
                'cash_collected_at',
                'cancel_reason',
                'last_status_changed_at',
            ] as $column) {
                if (Schema::hasColumn('transport_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
