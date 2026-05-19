<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->unsignedBigInteger('driver_id');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->integer('offer_rank')->default(1); // position dans le batch (1 = meilleur score)
            $table->float('driver_score')->nullable();
            $table->float('distance_km')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->string('decline_reason', 100)->nullable();
            $table->timestamps();

            $table->index(['delivery_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_offers');
    }
};
