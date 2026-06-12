<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('module', 80)->default('food')->index();
            $table->string('category', 80)->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 30)->default('open')->index();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('subject_type', 120)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('order_no', 80)->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_id')->nullable()->index();
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->unsignedBigInteger('transport_booking_id')->nullable()->index();
            $table->string('opened_by_type', 40)->nullable()->index();
            $table->unsignedBigInteger('opened_by_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to_id')->nullable()->index();
            $table->string('assigned_to_type', 40)->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->text('resolution_notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['module', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
