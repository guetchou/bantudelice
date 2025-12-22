<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('charges')) {
            Schema::create('charges', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->double('delivery_fee', 10, 2)->default(0);
                $table->double('tax', 5, 2)->default(0);
                $table->double('service_fee', 5, 2)->default(0);
                $table->double('min_order', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('charges');
    }
}

