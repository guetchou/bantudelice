<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeferredPaymentColumnsToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'checkout_snapshot')) {
                $table->json('checkout_snapshot')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'cash_collection_status')) {
                $table->string('cash_collection_status')->nullable()->after('checkout_snapshot');
            }
            if (!Schema::hasColumn('orders', 'cash_collected_at')) {
                $table->dateTime('cash_collected_at')->nullable()->after('cash_collection_status');
            }
            if (!Schema::hasColumn('orders', 'cash_collected_by')) {
                $table->unsignedBigInteger('cash_collected_by')->nullable()->after('cash_collected_at');
            }
            if (!Schema::hasColumn('orders', 'cash_collection_confirmed_at')) {
                $table->dateTime('cash_collection_confirmed_at')->nullable()->after('cash_collected_by');
            }
            if (!Schema::hasColumn('orders', 'cash_collection_reference')) {
                $table->string('cash_collection_reference')->nullable()->after('cash_collection_confirmed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'checkout_snapshot',
                'cash_collection_status',
                'cash_collected_at',
                'cash_collected_by',
                'cash_collection_confirmed_at',
                'cash_collection_reference',
            ]);
        });
    }
}
