<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickupColumnsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'fulfillment_mode')) {
                $table->string('fulfillment_mode', 30)->default('delivery')->after('driver_id');
            }

            if (!Schema::hasColumn('orders', 'pickup_code')) {
                $table->string('pickup_code', 20)->nullable()->after('fulfillment_mode');
            }

            if (!Schema::hasColumn('orders', 'customer_arrived_at')) {
                $table->timestamp('customer_arrived_at')->nullable()->after('ready_at');
            }

            if (!Schema::hasColumn('orders', 'customer_picked_up_at')) {
                $table->timestamp('customer_picked_up_at')->nullable()->after('customer_arrived_at');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['customer_picked_up_at', 'customer_arrived_at', 'pickup_code', 'fulfillment_mode'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
