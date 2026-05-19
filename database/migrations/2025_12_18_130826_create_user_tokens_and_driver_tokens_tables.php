<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTokensAndDriverTokensTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_tokens')) {
            Schema::create('user_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('device_tokens');
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('driver_tokens')) {
            Schema::create('driver_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('driver_id');
                $table->text('device_tokens');
                $table->timestamps();
                
                $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
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
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('driver_tokens');
    }
}
