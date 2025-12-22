<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_no')) {
                $table->string('order_no')->nullable()->after('id');
            }
            if (!Schema::hasColumn('orders', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('restaurant_id');
            }
            if (!Schema::hasColumn('orders', 'qty')) {
                $table->integer('qty')->default(1)->after('product_id');
            }
            if (!Schema::hasColumn('orders', 'price')) {
                $table->double('price', 10, 2)->default(0)->after('qty');
            }
            if (!Schema::hasColumn('orders', 'latitude')) {
                $table->string('latitude')->nullable()->after('price');
            }
            if (!Schema::hasColumn('orders', 'longitude')) {
                $table->string('longitude')->nullable()->after('latitude');
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
            $table->dropColumn(['order_no', 'product_id', 'qty', 'price', 'latitude', 'longitude']);
        });
    }
}


