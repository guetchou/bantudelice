<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddOnsTitlesTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('add_ons_titles')) {
            Schema::create('add_ons_titles', function (Blueprint $table) {
                $table->id();
                $table->string('title', 191);
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('restaurant_id');
                $table->timestamps();
                $table->index(['restaurant_id']);
                $table->index(['product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons_titles');
    }
}
