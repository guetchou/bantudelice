<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantFavoritesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('restaurant_favorites')) {
            return;
        }

        Schema::create('restaurant_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'restaurant_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_favorites');
    }
}
