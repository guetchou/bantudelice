<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_payout_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->string('label');
            $table->string('destination_type', 64);
            $table->text('destination');
            $table->boolean('verified')->default(false)->index();
            $table->string('verified_by', 191)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_payout_destinations');
    }
};
