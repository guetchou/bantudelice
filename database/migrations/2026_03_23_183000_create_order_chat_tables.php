<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('order_no', 100)->index();
            $table->unsignedBigInteger('customer_user_id')->index();
            $table->unsignedBigInteger('restaurant_user_id')->nullable()->index();
            $table->unsignedBigInteger('driver_id')->nullable()->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->string('last_message_by_role', 32)->nullable();
            $table->timestamp('customer_last_read_at')->nullable();
            $table->timestamp('restaurant_last_read_at')->nullable();
            $table->timestamp('driver_last_read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_chat_id')->index();
            $table->unsignedBigInteger('sender_user_id')->nullable()->index();
            $table->string('sender_role', 32)->index();
            $table->text('message');
            $table->timestamps();

            $table->index(['order_chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_chat_messages');
        Schema::dropIfExists('order_chats');
    }
};
