<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialEventsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('financial_events')) {
            return;
        }

        Schema::create('financial_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type', 60)->index();
            $table->string('provider', 40)->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('XAF');
            $table->string('status', 40)->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_events');
    }
}
