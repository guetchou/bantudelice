<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompletedOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('completed_orders')) {
            Schema::create('completed_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_no')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('restaurant_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->integer('qty')->default(1);
                $table->double('price', 10, 2)->default(0);
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
                $table->bigInteger('total_items')->default(1);
                $table->double('offer_discount', 10, 2)->default(0);
                $table->double('tax', 10, 2)->default(0);
                $table->double('delivery_charges', 10, 2)->default(0);
                $table->double('sub_total', 10, 2)->default(0);
                $table->double('total', 10, 2)->default(0);
                $table->double('admin_commission', 10, 2)->default(0);
                $table->double('restaurant_commission', 10, 2)->default(0);
                $table->double('driver_tip', 10, 2)->default(0);
                $table->enum('status', ['pending', 'assign', 'prepairing', 'completed', 'cancelled', 'scheduled'])->default('completed');
                $table->string('delivery_address')->nullable();
                $table->dateTime('scheduled_date')->nullable();
                $table->string('d_lat')->nullable();
                $table->string('d_lng')->nullable();
                $table->dateTime('ordered_time')->nullable();
                $table->dateTime('delivered_time')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('completed_orders');
    }
}


