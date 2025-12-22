<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantMediaTable extends Migration
{
    public function up()
    {
        Schema::create('restaurant_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('source', 20)->default('upload'); // upload|external
            $table->string('file_name')->nullable();
            $table->text('external_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('restaurant_id')
                ->references('id')
                ->on('restaurants')
                ->onDelete('cascade');

            $table->index(['restaurant_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_media');
    }
}


