<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_address')) {
            return;
        }

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('building_no')->nullable();
            $table->string('street_no')->nullable();
            $table->string('area');
            $table->string('floor')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('complete_address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_address');
    }
}
