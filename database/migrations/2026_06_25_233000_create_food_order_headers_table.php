<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('food_order_headers')) {
            return;
        }

        Schema::create('food_order_headers', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->unsignedBigInteger('restaurant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('primary_order_id')->nullable()->index();
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->string('fulfillment_mode', 20)->default('delivery');
            $table->string('business_status', 60)->nullable()->index();
            $table->string('payment_status', 40)->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('source_created_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_order_headers');
    }
};
