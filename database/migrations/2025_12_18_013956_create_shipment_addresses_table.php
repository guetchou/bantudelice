<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('type'); // pickup|dropoff
            $table->string('full_name');
            $table->string('phone');
            $table->string('city');
            $table->string('district');
            $table->string('address_line');
            $table->string('landmark')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_addresses');
    }
};

