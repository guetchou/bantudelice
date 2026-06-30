<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('business_status', 30)->nullable()->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('business_status');
            $table->timestamp('failed_at')->nullable()->after('confirmed_at');
            $table->timestamp('reversed_at')->nullable()->after('failed_at');
            $table->timestamp('refunded_at')->nullable()->after('reversed_at');
            $table->timestamp('reconciled_at')->nullable()->after('refunded_at');
            $table->index('business_status');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('payment_id');
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id');
            $table->string('target_reference', 120)->nullable();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('active');
            $table->string('idempotency_key', 150)->unique();
            $table->timestamp('allocated_at');
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('restrict');
            $table->index(['target_type', 'target_id']);
            $table->index(['payment_id', 'status']);
        });

        Schema::create('financial_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_type', 40);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('entry_type', 50);
            $table->string('direction', 10);
            $table->string('state', 20)->default('posted');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('XAF');
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('idempotency_key', 180)->unique();
            $table->unsignedBigInteger('related_entry_id')->nullable();
            $table->timestamp('effective_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('related_entry_id')
                ->references('id')
                ->on('financial_ledger_entries')
                ->onDelete('restrict');
            $table->index(['account_type', 'account_id', 'state']);
            $table->index(['source_type', 'source_id']);
            $table->index(['entry_type', 'effective_at']);
        });

        Schema::create('payment_reconciliation_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->string('case_type', 40);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('expected_amount')->nullable();
            $table->unsignedBigInteger('observed_amount')->nullable();
            $table->char('currency', 3)->default('XAF');
            $table->string('internal_status', 40)->nullable();
            $table->string('provider_status', 80)->nullable();
            $table->string('provider_reference', 150)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'severity']);
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_cases');
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('payment_allocations');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['business_status']);
            $table->dropColumn([
                'business_status',
                'confirmed_at',
                'failed_at',
                'reversed_at',
                'refunded_at',
                'reconciled_at',
            ]);
        });
    }
};
