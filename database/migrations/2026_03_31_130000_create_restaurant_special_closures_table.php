<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_special_closures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('label');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')
                ->references('id')
                ->on('restaurants')
                ->onDelete('cascade');

            $table->index(['restaurant_id', 'starts_on', 'ends_on'], 'rsc_restaurant_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_special_closures');
    }
};
