<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable(); // Précision GPS en mètres
            $table->decimal('heading', 5, 2)->nullable(); // Direction en degrés (0-360)
            $table->decimal('speed', 6, 2)->nullable(); // Vitesse en km/h
            $table->timestamp('timestamp')->useCurrent(); // Timestamp de la position
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->index(['driver_id', 'timestamp']);
            $table->index('timestamp'); // Pour les requêtes récentes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
