<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpsFieldsToShipmentsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'delivery_otp_code')) {
                $table->string('delivery_otp_code', 10)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('shipments', 'delivery_otp_expires_at')) {
                $table->dateTime('delivery_otp_expires_at')->nullable()->after('delivery_otp_code');
            }
            if (!Schema::hasColumn('shipments', 'cod_collected_at')) {
                $table->dateTime('cod_collected_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('shipments', 'last_status_changed_at')) {
                $table->dateTime('last_status_changed_at')->nullable()->after('cod_collected_at');
            }
            if (!Schema::hasColumn('shipments', 'delivered_latitude')) {
                $table->decimal('delivered_latitude', 10, 7)->nullable()->after('last_status_changed_at');
            }
            if (!Schema::hasColumn('shipments', 'delivered_longitude')) {
                $table->decimal('delivered_longitude', 10, 7)->nullable()->after('delivered_latitude');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            foreach ([
                'delivery_otp_code',
                'delivery_otp_expires_at',
                'cod_collected_at',
                'last_status_changed_at',
                'delivered_latitude',
                'delivered_longitude',
            ] as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
