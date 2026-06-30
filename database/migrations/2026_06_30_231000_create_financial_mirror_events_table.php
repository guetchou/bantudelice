<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_mirror_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_key', 190)->unique();
            $table->string('event_type', 80)->index();
            $table->string('source_type', 80)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->uuid('posting_batch_uuid')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status', 'created_at'], 'financial_mirror_event_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_mirror_events');
    }
};
