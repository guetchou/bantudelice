<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 140)->unique();
            $table->string('name', 160);
            $table->string('category', 24)->index();
            $table->string('purpose', 60)->index();
            $table->string('owner_type', 30)->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['owner_type', 'owner_id', 'purpose', 'currency'],
                'financial_accounts_owner_purpose_unique'
            );
        });

        Schema::create('financial_posting_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_type', 80)->index();
            $table->string('source_type', 80)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('idempotency_key', 190)->unique();
            $table->string('status', 20)->default('posted')->index();
            $table->unsignedBigInteger('reversal_of_batch_id')->nullable()->index();
            $table->timestamp('effective_at')->index();
            $table->timestamp('posted_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('reversal_of_batch_id')
                ->references('id')
                ->on('financial_posting_batches')
                ->restrictOnDelete();
        });

        Schema::create('financial_postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedSmallInteger('line_no');
            $table->string('direction', 8);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->unique(['batch_id', 'line_no'], 'financial_postings_batch_line_unique');
            $table->index(['account_id', 'created_at']);
            $table->index(['batch_id', 'direction']);

            $table->foreign('batch_id')
                ->references('id')
                ->on('financial_posting_batches')
                ->restrictOnDelete();
            $table->foreign('account_id')
                ->references('id')
                ->on('financial_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_postings');
        Schema::dropIfExists('financial_posting_batches');
        Schema::dropIfExists('financial_accounts');
    }
};
